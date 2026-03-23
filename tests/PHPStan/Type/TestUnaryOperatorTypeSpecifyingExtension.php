<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Fixture\TestUnaryOperand;
use function in_array;

/**
 * Test extension for verifying that unary operators call type specifying extensions.
 */
final class TestUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $operand): bool
	{
		$testType = new ObjectType(TestUnaryOperand::class);

		return in_array($operatorSigil, ['-', '+', '~'], true)
			&& $testType->isSuperTypeOf($operand)->yes();
	}

	public function specifyType(string $operatorSigil, Type $operand): Type
	{
		return new ObjectType(TestUnaryOperand::class);
	}

}
