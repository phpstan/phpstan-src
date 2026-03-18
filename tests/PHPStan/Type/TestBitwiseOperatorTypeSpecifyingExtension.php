<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Fixture\TestBitwiseOperand;
use function in_array;

/**
 * Test extension for verifying that bitwise operators call type specifying extensions.
 */
final class TestBitwiseOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		$testType = new ObjectType(TestBitwiseOperand::class);

		return in_array($operatorSigil, ['&', '|', '^', '<<', '>>'], true)
			&& $testType->isSuperTypeOf($leftSide)->yes()
			&& $testType->isSuperTypeOf($rightSide)->yes();
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		return new ObjectType(TestBitwiseOperand::class);
	}

}
