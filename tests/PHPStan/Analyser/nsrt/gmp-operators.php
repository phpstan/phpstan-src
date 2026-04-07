<?php

namespace GmpOperatorsTest;

use function PHPStan\Testing\assertType;

// =============================================================================
// Operator overloads
// =============================================================================

function gmpArithmeticOperators(\GMP $a, \GMP $b): void
{
	assertType('GMP', $a + $b);
	assertType('GMP', $a - $b);
	assertType('GMP', $a * $b);
	assertType('GMP', $a / $b);
	assertType('GMP', $a % $b);
	assertType('GMP', $a ** $b);
	assertType('GMP', -$a);
}

function gmpWithIntOperators(\GMP $a, int $i): void
{
	// GMP on left
	assertType('GMP', $a + $i);
	assertType('GMP', $a - $i);
	assertType('GMP', $a * $i);
	assertType('GMP', $a / $i);
	assertType('GMP', $a % $i);
	assertType('GMP', $a ** $i);

	// int on left (GMP on right)
	assertType('GMP', $i + $a);
	assertType('GMP', $i - $a);
	assertType('GMP', $i * $a);
	assertType('GMP', $i / $a);
	assertType('GMP', $i % $a);
	// Note: $i ** $a is not supported by GMP - exponent must be int
}

function gmpBitwiseOperators(\GMP $a, \GMP $b, int $i): void
{
	// GMP bitwise with GMP
	assertType('GMP', $a & $b);
	assertType('GMP', $a | $b);
	assertType('GMP', $a ^ $b);
	assertType('GMP', ~$a);
	assertType('GMP', $a << $b);
	assertType('GMP', $a >> $b);

	// GMP on left, int on right
	assertType('GMP', $a & $i);
	assertType('GMP', $a | $i);
	assertType('GMP', $a ^ $i);
	assertType('GMP', $a << $i);
	assertType('GMP', $a >> $i);

	// int on left, GMP on right
	assertType('GMP', $i & $a);
	assertType('GMP', $i | $a);
	assertType('GMP', $i ^ $a);
}

function gmpComparisonOperators(\GMP $a, \GMP $b, int $i): void
{
	// GMP compared with GMP
	assertType('bool', $a < $b);
	assertType('bool', $a <= $b);
	assertType('bool', $a > $b);
	assertType('bool', $a >= $b);
	assertType('bool', $a == $b);
	assertType('bool', $a != $b);
	assertType('int<-1, 1>', $a <=> $b);

	// GMP on left, int on right
	assertType('bool', $a < $i);
	assertType('bool', $a <= $i);
	assertType('bool', $a > $i);
	assertType('bool', $a >= $i);
	assertType('bool', $a == $i);
	assertType('bool', $a != $i);
	assertType('int<-1, 1>', $a <=> $i);

	// int on left, GMP on right
	assertType('bool', $i < $a);
	assertType('bool', $i <= $a);
	assertType('bool', $i > $a);
	assertType('bool', $i >= $a);
	assertType('bool', $i == $a);
	assertType('bool', $i != $a);
	assertType('int<-1, 1>', $i <=> $a);
}

function gmpAssignmentOperators(\GMP $a, int $i): void
{
	$x = $a;
	$x += $i;
	assertType('GMP', $x);

	$y = $a;
	$y -= $i;
	assertType('GMP', $y);

	$z = $a;
	$z *= $i;
	assertType('GMP', $z);
}

// =============================================================================
// gmp_* functions (corresponding to operator overloads)
// =============================================================================

function gmpArithmeticFunctions(\GMP $a, \GMP $b, int $i): void
{
	// gmp_add corresponds to +
	assertType('GMP', gmp_add($a, $b));
	assertType('GMP', gmp_add($a, $i));
	assertType('GMP', gmp_add($i, $a));

	// gmp_sub corresponds to -
	assertType('GMP', gmp_sub($a, $b));
	assertType('GMP', gmp_sub($a, $i));
	assertType('GMP', gmp_sub($i, $a));

	// gmp_mul corresponds to *
	assertType('GMP', gmp_mul($a, $b));
	assertType('GMP', gmp_mul($a, $i));
	assertType('GMP', gmp_mul($i, $a));

	// gmp_div_q corresponds to /
	assertType('GMP', gmp_div_q($a, $b));
	assertType('GMP', gmp_div_q($a, $i));

	// gmp_div is alias of gmp_div_q
	assertType('GMP', gmp_div($a, $b));

	// gmp_mod corresponds to %
	assertType('GMP', gmp_mod($a, $b));
	assertType('GMP', gmp_mod($a, $i));

	// gmp_pow corresponds to **
	assertType('GMP', gmp_pow($a, 2));
	assertType('GMP', gmp_pow($a, $i));

	// gmp_neg corresponds to unary -
	assertType('GMP', gmp_neg($a));

	// gmp_abs (no direct operator)
	assertType('GMP', gmp_abs($a));
}

function gmpBitwiseFunctions(\GMP $a, \GMP $b): void
{
	// gmp_and corresponds to &
	assertType('GMP', gmp_and($a, $b));

	// gmp_or corresponds to |
	assertType('GMP', gmp_or($a, $b));

	// gmp_xor corresponds to ^
	assertType('GMP', gmp_xor($a, $b));

	// gmp_com corresponds to ~
	assertType('GMP', gmp_com($a));
}

function gmpComparisonFunctions(\GMP $a, \GMP $b, int $i): void
{
	// gmp_cmp returns -1, 0, or 1 in practice, but stubs say int
	// TODO: Could be improved to int<-1, 1> like the <=> operator
	assertType('int', gmp_cmp($a, $b));
	assertType('int', gmp_cmp($a, $i));
}

function gmpFromInit(): void
{
	$x = gmp_init('1');
	assertType('GMP', $x);

	// Operator with gmp_init result
	$y = $x * 2;
	assertType('GMP', $y);

	$z = $x + gmp_init('5');
	assertType('GMP', $z);
}

function gmpWithNumericString(\GMP $a, string $s): void
{
	// GMP functions accept numeric strings
	assertType('GMP', gmp_add($a, '123'));
	assertType('GMP', gmp_mul($a, '456'));

	// General string (not numeric-string) has isNumericString()=Maybe
	// This catches TrinaryLogicMutator on line 53: isNumericString()->yes() → !isNumericString()->no()
	// Without mutation: Maybe.yes()=false → ErrorType
	// With mutation: !Maybe.no()=true → GMP (incorrect!)
	/** @phpstan-ignore binaryOp.invalid */
	assertType('*ERROR*', $a + $s);
	/** @phpstan-ignore binaryOp.invalid */
	assertType('*ERROR*', $s + $a);
}

/**
 * @param object $obj
 */
function nonGmpObjectsDoNotGetGmpTreatment($obj, int $i): void
{
	// Generic object should NOT be treated as GMP - the extension should not activate
	// (object is a supertype of GMP, but GMP is not a supertype of object)
	/** @phpstan-ignore binaryOp.invalid */
	assertType('*ERROR*', $obj + $i);
	/** @phpstan-ignore binaryOp.invalid */
	assertType('*ERROR*', $i + $obj);
}

/**
 * Tests for unary operators on non-GMP objects.
 * This catches IsSuperTypeOfCalleeAndArgumentMutator and TrinaryLogicMutator
 * on GmpUnaryOperatorTypeSpecifyingExtension line 27.
 *
 * When mutation swaps $gmpType->isSuperTypeOf($operand) to $operand->isSuperTypeOf($gmpType),
 * `object` would incorrectly activate the extension and return GMP.
 *
 * @param object $obj
 */
function unaryOperatorsOnObjectShouldError($obj): void
{
	// Without mutation: extension doesn't activate (GMP not supertype of object)
	// With mutation: extension activates (object IS supertype of GMP), returns GMP!
	/** @phpstan-ignore unaryOp.invalid */
	assertType('*ERROR*', -$obj);
	/** @phpstan-ignore unaryOp.invalid */
	assertType('*ERROR*', +$obj);
	/** @phpstan-ignore unaryOp.invalid */
	assertType('*ERROR*', ~$obj);
}
