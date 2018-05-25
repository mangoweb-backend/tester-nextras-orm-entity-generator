<?php declare(strict_types = 1);

namespace Mangoweb\Tester\NextrasOrmEntityGenerator;


abstract class EntityFactory
{
	/** @var int[] */
	private $counter = [];


	public function supports(string $entityClass): bool
	{
		return method_exists($this, $this->formatMethod($entityClass));
	}


	public function create(string $entityClass, array $data, EntityGenerator $generator)
	{
		$method = $this->formatMethod($entityClass);
		return $this->$method($data, $generator);
	}


	protected function counter(string $type, string $prefix = '', string $suffix = ''): string
	{
		if (!isset($this->counter[$type])) {
			$this->counter[$type] = 1;

		} else {
			$this->counter[$type]++;
		}

		return $prefix . $this->counter[$type] . $suffix;
	}


	protected function formatMethod(string $entityClass): string
	{
		$pos = strrpos($entityClass, '\\');
		return 'create' . substr($entityClass, $pos + 1);
	}


	protected function verifyData(array $allowedKeys, array $data): void
	{
		$additionalKeys = array_diff(array_keys($data), $allowedKeys);
		assert(count($additionalKeys) === 0, 'additional data keys found: ' . implode(', ', $additionalKeys));
	}
}
