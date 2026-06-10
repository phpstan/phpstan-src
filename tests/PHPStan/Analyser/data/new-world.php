<?php declare(strict_types = 1);

namespace NewWorldTypeInference;

use function PHPStan\Testing\assertType;

class Foo
{

	public function scalarsAndAssigns(): void
	{
		$a = 1;
		assertType('1', $a);

		$b = 'foo';
		assertType('\'foo\'', $b);

		$c = 1.5;
		assertType('1.5', $c);

		$d = $e = 5;
		assertType('5', $d);
		assertType('5', $e);
	}

	public function functionCalls(int $i, string $s): void
	{
		assertType('int', $i);
		assertType('string', $s);

		$len = strlen($s);
		assertType('int<0, max>', $len);

		$cnt = strlen('abc');
		assertType('3', $cnt);

		$abs = abs($i);
		assertType('int<0, max>', $abs);

		$abs2 = abs(7);
		assertType('7', $abs2);

		$nested = strlen(strtoupper($s));
		assertType('int<0, max>', $nested);

		$pi = pi();
		assertType('float', $pi);
	}

}
