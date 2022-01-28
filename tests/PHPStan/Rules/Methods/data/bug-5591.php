<?php

namespace Bug5591;

class EntityA {}
class EntityB {}

/**
 * @template TEntity as object
 */
class TestClass
{
	/** @var class-string<TEntity> The fully-qualified (::class) class name of the entity being managed. */
	protected string $entityClass;

	/** @param TEntity|null $record */
	public function outerMethod(?object $record = null): void
	{
		$record = $this->innerMethod($record);
	}

	/**
	 * @param TEntity|null $record
	 *
	 * @return TEntity
	 */
	public function innerMethod(?object $record = null): object
	{
		return $record ?? new ($this->entityClass)();
	}
}

/**
 * @template TEntity as EntityA|EntityB
 * @extends TestClass<TEntity>
 */
class TestClass2 extends TestClass
{
	public function outerMethod(?object $record = null): void
	{
		$this->innerMethod($record);
	}
}
