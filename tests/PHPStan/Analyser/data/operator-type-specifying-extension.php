<?php declare(strict_types = 1);

namespace OperatorExtensionTest;

use PHPStan\Fixture\TestBitwiseOperand;
use PHPStan\Fixture\TestDecimal;
use function PHPStan\Testing\assertType;

// =============================================================================
// Bitwise operator extension tests
// =============================================================================

function testBitwiseAnd(TestBitwiseOperand $a, TestBitwiseOperand $b): void
{
	assertType('PHPStan\Fixture\TestBitwiseOperand', $a & $b);
}

function testBitwiseOr(TestBitwiseOperand $a, TestBitwiseOperand $b): void
{
	assertType('PHPStan\Fixture\TestBitwiseOperand', $a | $b);
}

function testBitwiseXor(TestBitwiseOperand $a, TestBitwiseOperand $b): void
{
	assertType('PHPStan\Fixture\TestBitwiseOperand', $a ^ $b);
}

// =============================================================================
// Arithmetic operator extension tests (via TestDecimal)
// =============================================================================

function testArithmeticAdd(TestDecimal $a, TestDecimal $b): void
{
	assertType('PHPStan\Fixture\TestDecimal', $a + $b);
}

function testArithmeticSub(TestDecimal $a, TestDecimal $b): void
{
	assertType('PHPStan\Fixture\TestDecimal', $a - $b);
}

function testArithmeticMul(TestDecimal $a, TestDecimal $b): void
{
	assertType('PHPStan\Fixture\TestDecimal', $a * $b);
}

function testArithmeticDiv(TestDecimal $a, TestDecimal $b): void
{
	assertType('PHPStan\Fixture\TestDecimal', $a / $b);
}

function testArithmeticPow(TestDecimal $a, TestDecimal $b): void
{
	assertType('PHPStan\Fixture\TestDecimal', $a ** $b);
}
