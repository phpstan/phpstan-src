<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class GmpOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		if ($leftSide instanceof NeverType || $rightSide instanceof NeverType) {
			return false;
		}

		$gmpType = new ObjectType('GMP');

		return in_array($operatorSigil, ['+', '-', '*', '/', '**', '%', '&', '|', '^', '<<', '>>', '<', '<=', '>', '>=', '==', '!=', '<=>'], true)
			&& (
				$gmpType->isSuperTypeOf($leftSide)->yes()
				|| $gmpType->isSuperTypeOf($rightSide)->yes()
			);
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		$gmpType = new ObjectType('GMP');
		$otherSide = $gmpType->isSuperTypeOf($leftSide)->yes()
			? $rightSide
			: $leftSide;

		// Comparison operators return bool or int (for spaceship)
		if (in_array($operatorSigil, ['<', '<=', '>', '>=', '==', '!='], true)) {
			return new BooleanType();
		}

		if ($operatorSigil === '<=>') {
			return IntegerRangeType::fromInterval(-1, 1);
		}

		// GMP can operate with: GMP, int, or numeric-string
		if (
			$otherSide->isInteger()->yes()
			|| $otherSide->isNumericString()->yes()
			|| $gmpType->isSuperTypeOf($otherSide)->yes()
		) {
			return $gmpType;
		}

		return new ErrorType();
	}

}
