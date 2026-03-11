<?php declare(strict_types = 1);

namespace Bug4971;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
interface IFoo
{
	/** @param T $v */
	public function __construct($v);
}

/**
 * @template T
 * @implements IFoo<T>
 */
class Foo implements IFoo
{
	/** @var T */
	private $v; // @phpstan-ignore property.uninitializedReadonly

	/**
	 * @param T $v
	 */
	public function __construct($v)
	{
		$this->v = $v;
	}
}

/**
 * @template T
 * @template K of IFoo
 * @param T $v
 * @param class-string<K> $class
 * @return K
 */
function make1($v, string $class)
{
	return new $class($v);
}

/**
 * @template T
 * @template K of IFoo
 * @param T $v
 * @param class-string<K> $class
 * @return K<T>
 */
function make2($v, string $class)
{
	return new $class($v);
}

$obj1 = make1(1, Foo::class);
assertType('Bug4971\Foo', $obj1);

$obj2 = make2(1, Foo::class);
assertType('Bug4971\Foo<int>', $obj2);
