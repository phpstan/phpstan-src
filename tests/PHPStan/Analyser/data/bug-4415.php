<?php

namespace Bug4415;

use function PHPStan\Analyser\assertType;

/**
 * @implements \IteratorAggregate<int, string>
 */
class Foo implements \IteratorAggregate
{

	public function getIterator(): \Iterator
	{

	}

}

function (Foo $foo): void {
	foreach ($foo as $k => $v) {
		assertType('int', $k);
		assertType('string', $v);
	}
};
