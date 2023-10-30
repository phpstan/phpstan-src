<?php // lint >= 8.1

namespace Bug10049;

/**
 * @template SELF of SimpleEntity
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
