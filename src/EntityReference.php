<?php declare(strict_types = 1);

namespace Mangoweb\Tester\NextrasOrmEntityGenerator;

use Nextras\Orm\Entity\IEntity;


class EntityReference
{
	/** @var array */
	private $data;

	/** @var null|IEntity */
	private $entity;

	/** @var null|self */
	private $masterReference;


	public function __construct(array $data = [])
	{
		$this->data = $data;
	}


	public function getData(): array
	{
		return $this->data;
	}


	public function hasEntity(): bool
	{
		return $this->entity !== null || ($this->masterReference && $this->masterReference->hasEntity());
	}


	public function getEntity(): IEntity
	{
		if ($this->masterReference) {
			return $this->masterReference->getEntity();
		}

		assert($this->entity !== null);
		return $this->entity;
	}


	public function setEntity(?IEntity $entity): void
	{
		if ($this->masterReference) {
			$this->masterReference->setEntity($entity);

		} else {
			assert($this->entity === null);
			$this->entity = $entity;
		}
	}


	public function setMasterReference(?self $masterReference): void
	{
		assert($this->masterReference === null);
		assert($this->entity === null);
		$this->masterReference = $masterReference;
	}
}
