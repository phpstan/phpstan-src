<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11374Types;

use function PHPStan\Testing\assertType;

final class Checker
{
	/** @phpstan-assert-if-true float $value */
	public function isFloat(mixed $value): bool
	{
		return is_float($value);
	}

	/** @phpstan-assert-if-false float $value */
	public function isNotFloat(mixed $value): bool
	{
		return !is_float($value);
	}

	/** @phpstan-assert-if-true !float $value */
	public function isNotFloatNegated(mixed $value): bool
	{
		return !is_float($value);
	}

	/** @phpstan-assert-if-true float $value */
	public static function isFloatStatic(mixed $value): bool
	{
		return is_float($value);
	}

	/** @phpstan-assert float $value */
	public function assertFloat(mixed $value): bool
	{
		return true;
	}

	/** @phpstan-assert-if-true =float $value */
	public function isFloatEquality(mixed $value): bool
	{
		return is_float($value);
	}
}

/** @phpstan-assert-if-true float $value */
function isFloat(mixed $value): bool
{
	return is_float($value);
}

function (Checker $c, float $f, int $i, float|int $fi, mixed $m): void {
	assertType('true', $c->isFloat($f));
	assertType('false', $c->isFloat($i));
	assertType('bool', $c->isFloat($fi));
	assertType('bool', $c->isFloat($m));

	assertType('false', $c->isNotFloat($f));
	assertType('true', $c->isNotFloat($i));
	assertType('bool', $c->isNotFloat($fi));

	assertType('false', $c->isNotFloatNegated($f));
	assertType('true', $c->isNotFloatNegated($i));
	assertType('bool', $c->isNotFloatNegated($fi));

	assertType('true', Checker::isFloatStatic($f));
	assertType('false', Checker::isFloatStatic($i));
	assertType('bool', Checker::isFloatStatic($fi));

	assertType('true', isFloat($f));
	assertType('false', isFloat($i));
	assertType('bool', isFloat($fi));

	assertType('bool', $c->assertFloat($i));
	assertType('bool', $c->isFloatEquality($f));
};
