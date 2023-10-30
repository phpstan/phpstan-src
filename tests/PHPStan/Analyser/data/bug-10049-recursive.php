<?php declare(strict_types = 1);

namespace Bug10049Recursive;

/**
 * @template SELF of SimpleEntity<SELF>
 */
abstract class SimpleEntity
{
	/**
	 * @param SimpleTable<SELF> $table
	 */
	public function __construct(protected readonly SimpleTable $table)
	{
	}
}

/**
 * @template-covariant E of SimpleEntity
 */
class SimpleTable
{
	/**
	 * @template ENTITY of SimpleEntity
	 *
	 * @param class-string<ENTITY> $className
	 *
	 * @return SimpleTable<ENTITY>
	 */
	public static function table(string $className, string $name): SimpleTable
	{
		return new SimpleTable($className, $name);
	}

	/**
	 * @param class-string<E> $className
	 */
	private function __construct(readonly string $className, readonly string $table)
	{
	}
}

/**
 * @template-extends SimpleEntity<TestEntity>
 */
class TestEntity extends SimpleEntity
{
	public function __construct()
	{
		$table = SimpleTable::table(TestEntity::class, 'testentity');
		parent::__construct($table);
	}
}


/**
 * @template-extends SimpleEntity<AnotherEntity>
 */
class AnotherEntity extends SimpleEntity
{
	public function __construct()
	{
		$table = SimpleTable::table(AnotherEntity::class, 'anotherentity');
		parent::__construct($table);
	}
}
