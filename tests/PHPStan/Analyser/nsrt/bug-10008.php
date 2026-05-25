<?php

declare(strict_types = 1);

namespace Bug10008;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param \mysqli_result $r
	 * @param \Traversable<array{email: string, adaid: int<-32768, 32767>}> $t
	 */
	public function sayHello($r, $t): void
	{
		$x = $r;
		if (rand(0,1)) {
			$x = $t;
		}
		assertType('mysqli_result|Traversable<mixed, array{email: string, adaid: int<-32768, 32767>}>', $x);
	}

	/**
	 * @param \Iterator<int, string> $a
	 * @param \Traversable<int, array{foo: int}> $b
	 */
	public function testDifferentValueTypes($a, $b): void
	{
		$x = $a;
		if (rand(0,1)) {
			$x = $b;
		}
		assertType('Iterator<int, string>|Traversable<int, array{foo: int}>', $x);
	}
}
