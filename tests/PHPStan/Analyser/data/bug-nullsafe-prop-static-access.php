<?php declare(strict_types=1); // onlyif PHP_VERSION_ID >= 80000

namespace BugNullsafePropStaticAccess;

class A
{
	public function __construct(public readonly B $b)
	{}
}

class B
{
	public static int $value = 0;

	public static function get(): string
	{
		return 'B';
	}
}

function foo(?A $a): void
{
	\PHPStan\Testing\assertType('string|null', $a?->b::get());
	\PHPStan\Testing\assertType('string|null', $a?->b->get());

	\PHPStan\Testing\assertType('int|null', $a?->b::$value);
	\PHPStan\Testing\assertType('int|null', $a?->b->value);
}
