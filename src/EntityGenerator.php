<?php declare(strict_types = 1);

namespace Mangoweb\Tester\NextrasOrmEntityGenerator;

use Nextras\Orm\Entity\IEntity;
use Nextras\Orm\Model\IModel;


class EntityGenerator
{
	/** @var IModel */
	private $orm;

	/** @var EntityFactory[] */
	private $factories;


	public function __construct(IModel $orm)
	{
		$this->orm = $orm;
	}


	public function addFactory(EntityFactory $entityFactory): void
	{
		$this->factories[] = $entityFactory;
	}


	public function create(string $class, array $data = [])
	{
		array_walk_recursive($data, function (&$value): void {
			if ($value instanceof IEntity && $value->isPersisted()) {
				$value = $this->reload($this->orm, $value);
			}
		});

		foreach ($this->factories as $factory) {
			if ($factory->supports($class)) {
				$entity = $factory->create($class, $data, $this);

				if ($entity instanceof IEntity) {
					$entity = $this->save($entity);
				}

				return $entity;
			}
		}

		throw new \LogicException('not implemented: ' . $class);
	}


	public function createInOrm(IModel $orm, string $class, array $data = []): IEntity
	{
		$entity = $this->create($class, $data);
		$entity = $this->reload($orm, $entity);

		return $entity;
	}


	/**
	 * @param  string      $class
	 * @param  int|array[] $entities
	 * @param  array       $commonData
	 * @return array
	 */
	public function createList(string $class, $entities, array $commonData = []): array
	{
		if (is_int($entities)) {
			$entities = array_fill(0, $entities, []);
		}

		$result = [];
		foreach ($entities as $entityData) {
			$result[] = $this->create($class, $entityData + $commonData);
		}

		return $result;
	}


	/**
	 * @param  string                                    $class
	 * @param  array|bool|IEntity|EntityReference|object $request
	 * @param  array                                     $additionalData
	 * @return object|null
	 */
	public function maybeCreate(string $class, $request, array $additionalData = [])
	{
		if (is_a($request, $class)) {
			return $request;

		} elseif ($request instanceof EntityReference) {
			if ($request->hasEntity()) {
				return $request->getEntity();

			} else {
				$data = $request->getData();
			}

		} elseif (is_bool($request)) {
			if ($request === false) {
				return null;

			} else {
				$data = [];
			}

		} elseif (is_array($request)) {
			$data = $request;

		} else {
			throw new \LogicException();
		}

		$merged = self::mergeConfig($data, $additionalData);
		$entity = $this->create($class, $merged);

		if ($request instanceof EntityReference) {
			$request->setEntity($entity);
		}

		return $entity;
	}


	public function refreshAll(): void
	{
		$this->orm->refreshAll();
	}


	public static function mergeConfig($left, $right)
	{
		if (is_array($left) && is_array($right)) {
			foreach ($left as $key => $val) {
				if (isset($right[$key])) {
					$val = static::mergeConfig($val, $right[$key]);
				}

				$right[$key] = $val;
			}
			return $right;

		} elseif ($left instanceof EntityReference || $right instanceof EntityReference) {
			$val = self::mergeConfig($left instanceof EntityReference ? $left->getData() : $left, $right instanceof EntityReference ? $right->getData() : $right);
			assert(is_array($val));
			$ref = new EntityReference($val);

			if ($left instanceof EntityReference) {
				$left->setMasterReference($ref);
			}

			if ($right instanceof EntityReference) {
				$right->setMasterReference($ref);
			}

			return $ref;

		} else {
			return $left;
		}
	}


	private function reload(IModel $orm, IEntity $entity): IEntity
	{
		$repository = $orm->getRepositoryForEntity($entity);
		$reloaded = $repository->getById($entity->getPersistedId());
		assert($reloaded !== null);

		return $reloaded;
	}


	private function save(IEntity $entity): IEntity
	{
		$this->orm->persistAndFlush($entity);
		return $entity;
	}
}
