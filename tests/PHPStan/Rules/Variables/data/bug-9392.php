<?php // lint >= 8.0

namespace Bug9392;

use function PHPStan\Testing\assertType;

class Range
{
	public function __construct(
		public ?string $notInRangeMessage = null,
		public mixed $min = null,
		public mixed $max = null,
	) {
	}
}

new Range(
	min: $min = 20 * 100,
	max: $max = 5_000 * 100,
	notInRangeMessage: sprintf('The price must be between %s and %s.', round($min / 100, 2), round($max / 100, 2)),
);

assertType('2000', $min);
assertType('500000', $max);

function foo(?string $c = null, mixed $a = null, mixed $b = null): void
{
}

foo(
	a: $a = 10,
	b: $b = 20,
	c: sprintf('%s %s', $a, $b),
);

class Foo
{
	public function bar(?string $c = null, mixed $a = null, mixed $b = null): void
	{
	}

	public static function baz(?string $c = null, mixed $a = null, mixed $b = null): void
	{
	}
}

$foo = new Foo();

$foo->bar(
	a: $x = 10,
	b: $y = 20,
	c: sprintf('%s %s', $x, $y),
);

Foo::baz(
	a: $p = 10,
	b: $q = 20,
	c: sprintf('%s %s', $p, $q),
);

// Mixed positional and named args
function mixed_args(int $first, ?string $c = null, mixed $a = null, mixed $b = null): void
{
}

mixed_args(
	1,
	a: $m1 = 10,
	b: $m2 = 20,
	c: sprintf('%s %s', $m1, $m2),
);

// Variable assigned in named arg used after the call
function after_call(?string $c = null, mixed $a = null): void
{
}

after_call(
	a: $afterVar = 42,
	c: (string) $afterVar,
);

echo $afterVar;
