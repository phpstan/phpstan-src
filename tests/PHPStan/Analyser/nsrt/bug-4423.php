<?php declare(strict_types = 1);

namespace Bug4423;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Bar {}

/**
 * @template K
 * @property-read Bar<K> $bar
 * @method Bar<K> doBar()
 */
trait Foo {

	/** @var Bar<K> */
	public $baz;

	/** @param K $k */
	public function doFoo($k)
	{
		assertType('T (class Bug4423\Child, argument)', $k);
		//assertType('Bug4423\Bar<T (class Bug4423\Child, argument)>', $this->bar);
		assertType('Bug4423\Bar<T (class Bug4423\Child, argument)>', $this->baz);
		//assertType('Bug4423\Bar<T (class Bug4423\Child, argument)>', $this->doBar());
		assertType('Bug4423\Bar<T (class Bug4423\Child, argument)>', $this->doBaz());
	}

	/** @return Bar<K> */
	public function doBaz()
	{

	}

}

/**
 * @template T
 * @template K
 */
class Base {

}

/**
 * @template T
 * @extends Base<int, T>
 */
class Child extends Base {
	/** @phpstan-use Foo<T> */
	use Foo;
}

function (Child $child): void {
	/** @var Child<int> $child */
	assertType('Bug4423\Child<int>', $child);
	//assertType('Bug4423\Bar<int>', $child->bar);
	assertType('Bug4423\Bar<int>', $child->baz);
	//assertType('Bug4423\Bar<int>', $child->doBar());
	assertType('Bug4423\Bar<int>', $child->doBaz());
};
