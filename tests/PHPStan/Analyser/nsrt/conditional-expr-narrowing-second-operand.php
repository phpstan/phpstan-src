<?php declare(strict_types = 1);

namespace ConditionalExprNarrowingSecondOperand;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var array{int, int}|null */
	public ?array $unsealed = null;

}

function test(Foo $a, Foo $b, bool $other): void
{
	$bothDefinite = $a->unsealed !== null && $b->unsealed !== null;

	// $bothDefinite as the first && operand
	if ($bothDefinite && $other) {
		assertType('array{int, int}', $a->unsealed);
		assertType('array{int, int}', $b->unsealed);
	}

	// $bothDefinite as the second && operand - regressed to array{int, int}|null
	// because filterBySpecifiedTypes read the un-narrowed $bothDefinite via getType()
	if ($other && $bothDefinite) {
		assertType('array{int, int}', $a->unsealed);
		assertType('array{int, int}', $b->unsealed);
	}
}
