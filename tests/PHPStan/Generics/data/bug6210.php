<?php

namespace Generics\Bug6210;

/**
 * @template TEntityClass of object
 */
class HelloWorld
{
	/**
	 * @phpstan-param TEntityClass $entity
	 */
	public function provide($entity, ?string $value): void
	{
		if (null !== $value) {
			if (!method_exists($entity, 'getId')) {
				throw new \InvalidArgumentException();
			}
		}
		$this->show($entity);
	}

	/**
	 * @phpstan-param TEntityClass $entity
	 */
	private function show($entity): void
	{
	}
}
