<?php // lint >= 8.1

namespace TentativeReturnTypeNative;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/**
 * @extends \ArrayObject<int, string>
 */
class MyArrayObject extends \ArrayObject
{

}

/**
 * @implements \IteratorAggregate<int, string>
 */
class MyAggregate implements \IteratorAggregate
{

	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		return new \ArrayIterator([]);
	}

}

class Foo
{

	/**
	 * @param \ArrayObject<int, string> $arrayObject
	 */
	public function doFoo(\ArrayObject $arrayObject, MyArrayObject $myArrayObject, \DateTimeImmutable $date, \SplObjectStorage $storage, MyAggregate $aggregate): void
	{
		assertType('int<0, max>', $arrayObject->count());
		assertNativeType('int', $arrayObject->count());
		assertNativeType('int', $myArrayObject->count());
		assertNativeType('int', $date->getTimestamp());
		assertNativeType('string', $storage->getHash(new \stdClass()));
		assertNativeType('mixed', $aggregate->getIterator());
	}

}
