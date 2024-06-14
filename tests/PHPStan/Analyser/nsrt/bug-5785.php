<?php declare(strict_types=1);

namespace Bug5785;

use function PHPStan\Testing\assertType;

class HelloWorld
{
}

class IterableHelper
{
	/**
	 * @template T
	 * @param iterable<T> $iterable
	 * @param-out iterable<T> $iterable
	 */
	public static function act(iterable &$iterable): void
	{
	}
}

function doFoo() {
	/** @var HelloWorld[] $a */
	$a = [];

	assertType('array<Bug5785\HelloWorld>', $a);
	IterableHelper::act($a);
	assertType('iterable<Bug5785\HelloWorld>', $a);

}


