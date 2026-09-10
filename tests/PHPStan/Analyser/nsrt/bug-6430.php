<?php declare(strict_types = 1);

namespace Bug6430;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class HelloWorld
{
	/**
	 * @param array<int, (\Closure(TValue, TKey): mixed)> $callback
	 */
	public function sayHello($callback): void
	{

	}
}

/** @var HelloWorld<int, string> */
$a = new HelloWorld;

$a->sayHello([function ($u, $i) {
	assertType('string', $u);
	assertType('int', $i);
	return true;
}]);
