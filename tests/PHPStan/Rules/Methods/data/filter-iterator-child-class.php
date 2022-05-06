<?php declare(strict_types=1);

namespace FilterIteratorChild;

use function PHPStan\Testing\assertType;

class ArchivableFilesFinder extends \FilterIterator
{

	public function __construct()
	{
		parent::__construct(new \ArrayIterator([]));
	}

	public function accept(): bool
	{
		return true;
	}
}

class ArchivableFilesFinderTest
{

	public function doFoo(ArchivableFilesFinder $finder): void
	{
		foreach ($finder as $f) {
			assertType('mixed', $f);
		}
	}

}

interface IteratorChild extends \Iterator
{

	/** @return int */
	#[\ReturnTypeWillChange]
	public function key();

	/** @return int */
	#[\ReturnTypeWillChange]
	public function current();

}

/** @extends \Iterator<mixed, mixed> */
interface IteratorChild2 extends \Iterator
{

	/** @return int */
	#[\ReturnTypeWillChange]
	public function key();

	/** @return int */
	#[\ReturnTypeWillChange]
	public function current();

}

class Foo
{

	public function doFoo(IteratorChild $c)
	{
		foreach ($c as $k => $v) {
			assertType('int', $k);
			assertType('int', $v);
		}
	}

	public function doFoo2(IteratorChild2 $c)
	{
		foreach ($c as $k => $v) {
			assertType('mixed', $k);
			assertType('mixed', $v);
		}
	}

}

interface IteratorChild3 extends \Iterator
{

}

class IteratorChildTest
{

	public function doFoo(IteratorChild3 $c)
	{

	}

}
