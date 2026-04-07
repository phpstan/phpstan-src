<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;
use function in_array;

#[AutowiredService]
final class GmpUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $operand): bool
	{
		if ($operand instanceof NeverType) {
			return false;
		}

		if (!in_array($operatorSigil, ['-', '+', '~'], true)) {
			return false;
		}

		$gmpType = new ObjectType('GMP');
		return $gmpType->isSuperTypeOf($operand)->yes();
	}

	public function specifyType(string $operatorSigil, Type $operand): Type
	{
		return new ObjectType('GMP');
	}

}
