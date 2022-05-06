<?php

declare(strict_types=1);

namespace Bug5591;

class EntityA {}
class EntityB {}

/**
 * @template TEntity as object
 */
class TestClass
{
    /** @var class-string<TEntity> The fully-qualified (::class) class name of the entity being managed. */
    protected $entityClass;

    /** @param TEntity|null $record */
    public function outerMethod($record = null): void
    {
        $record = $this->innerMethod($record);
    }

    /**
     * @param TEntity|null $record
     *
     * @return TEntity
     */
    public function innerMethod($record = null): object
    {
		$class = $this->entityClass;
        return new $class();
    }
}

/**
 * @template TEntity as EntityA|EntityB
 * @extends TestClass<TEntity>
 */
class TestClass2 extends TestClass
{
    public function outerMethod($record = null): void
    {
        $record = $this->innerMethod($record);
    }
}
