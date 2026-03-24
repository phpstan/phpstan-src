<?php

namespace UnaryOperatorTypeSpecifyingExtensionTest;

use PHPStan\Fixture\TestUnaryOperand;
use function PHPStan\Testing\assertType;

function testUnaryMinus(TestUnaryOperand $a): void
{
	assertType('PHPStan\Fixture\TestUnaryOperand', -$a);
}

function testUnaryPlus(TestUnaryOperand $a): void
{
	assertType('PHPStan\Fixture\TestUnaryOperand', +$a);
}

function testBitwiseNot(TestUnaryOperand $a): void
{
	assertType('PHPStan\Fixture\TestUnaryOperand', ~$a);
}
