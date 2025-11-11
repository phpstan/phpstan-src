<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class BcMathNumberOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		if (!$this->phpVersion->supportsBcMathNumberOperatorOverloading() || !$leftSide->isNever()->no() || !$rightSide->isNever()->no()) {
			return false;
		}

		$bcMathNumberType = new ObjectType('BcMath\Number');

		return in_array($operatorSigil, ['-', '+', '*', '/', '**', '%', '<', '<=', '>', '>=', '==', '!=', '<=>'], true)
			&& (
				$bcMathNumberType->isSuperTypeOf($leftSide)->yes()
				|| $bcMathNumberType->isSuperTypeOf($rightSide)->yes()
			);
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		$bcMathNumberType = new ObjectType('BcMath\Number');
		$otherSide = $bcMathNumberType->isSuperTypeOf($leftSide)->yes()
			? $rightSide
			: $leftSide;

		if ($otherSide->isFloat()->yes()) {
			return new ErrorType();
		}

		if (in_array($operatorSigil, ['<', '<=', '>', '>=', '==', '!='], true)) {
			return new BooleanType();
		}

		if ($operatorSigil === '<=>') {
			return IntegerRangeType::fromInterval(-1, 1);
		}

		if (
			$otherSide->isInteger()->yes()
			|| $otherSide->isNumericString()->yes()
			|| $bcMathNumberType->isSuperTypeOf($otherSide)->yes()
		) {
			return $bcMathNumberType;
		}

		return new ErrorType();
	}

}
