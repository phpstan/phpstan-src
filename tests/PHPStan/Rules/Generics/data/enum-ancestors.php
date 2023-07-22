<?php // lint >= 8.1

namespace EnumGenericAncestors;

interface NonGeneric
{

}

/**
 * @template T of object
 * @template U
 */
interface Generic
{

}

/**
 * @implements NonGeneric<int>
 */
enum Foo
{

}

enum Foo2 implements NonGeneric
{

}

/**
 * @implements NonGeneric<int>
 */
enum Foo3 implements NonGeneric
{

}

enum Foo4 implements Generic
{

}

/**
 * @implements Generic<\stdClass, int>
 */
enum Foo5 implements Generic
{

}

/**
 * @implements Generic<\stdClass>
 */
enum Foo6 implements Generic
{

}

/**
 * @extends Generic<\stdClass, int>
 */
enum Foo7
{

}

/**
 * @extends \Traversable<int, int>
 */
interface TraversableInt extends \Traversable
{

}

/**
 * @implements \IteratorAggregate<string, string>
 */
enum Foo8 implements TraversableInt, \IteratorAggregate
{

	public function getIterator()
	{
		return new \ArrayIterator([]);
	}

}

/**
 * @implements Generic<covariant NonGeneric, int>
 */
enum TypeProjection implements Generic
{

}
