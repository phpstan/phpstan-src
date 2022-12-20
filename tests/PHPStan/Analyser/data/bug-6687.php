<?php declare(strict_types = 1);

namespace Bug6687;

use DateTimeImmutable;

use function PHPStan\Testing\assertType;

const BAZ = 'BAZ';

class Bug6687
{

	function foo(string $a): void
	{
		if ($a === DateTimeImmutable::class || is_subclass_of($a, DateTimeImmutable::class)) {
			assertType('class-string<DateTimeImmutable>', $a);
		}
	}

	function bar(string $a): void
	{
		if ($a === 'FOO' || is_subclass_of($a, 'FOO')) {
			assertType('class-string<FOO>', $a);
		}
	}

	function baz(string $a): void
	{
		if ($a === BAZ || is_subclass_of($a, BAZ)) {
			assertType("'BAZ'|class-string<BAZ>", $a);
		}
	}

}
