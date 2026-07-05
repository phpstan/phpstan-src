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

	private ObjectType $gmpType;

	public function __construct()
	{
		$this->gmpType = new ObjectType('GMP');
	}

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		if ($leftSide instanceof NeverType || $rightSide instanceof NeverType) {
			return false;
		}

		return in_array($operatorSigil, ['+', '-', '*', '/', '**', '%', '&', '|', '^', '<<', '>>', '<', '<=', '>', '>=', '==', '!=', '<=>'], true)
			&& (
				$this->gmpType->isSuperTypeOf($leftSide)->yes()
				|| $this->gmpType->isSuperTypeOf($rightSide)->yes()
			);
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		$otherSide = $this->gmpType->isSuperTypeOf($leftSide)->yes()
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
			|| $this->gmpType->isSuperTypeOf($otherSide)->yes()
		) {
			return $this->gmpType;
		}

		return new ErrorType();
	}

}
