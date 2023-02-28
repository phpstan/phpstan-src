<?php declare(strict_types=1);

namespace GetObjectVars;

use function PHPStan\Testing\assertType;
class Base
{

	public int $a;
	public string $b;
	protected string $c;
	private bool $d;

	public function getVars(): void
	{
		assertType('array{a: int, b: string, c: string, d: bool}', get_object_vars($this));
	}

}

class Other {
	public int $one;
	private bool $two;
	public bool $three;
}

class Extended extends Base
{

	public function getExtendedVars(): void
	{
		assertType('array{a: int, b: string, c: string}', get_object_vars($this));
	}

}

function check(Base $base, Base|Other $class, string $input): void
{
	assertType('array{a: int, b: string}', get_object_vars($base));
	assertType('array{a: int, b: string}|array{one: int, three: bool}', get_object_vars($class));
	assertType('array<string, mixed>', get_object_vars($input));
	assertType('array<string, mixed>', get_object_vars());
}


