<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;
use function in_array;

final class BcMathNumberOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		if (!$this->phpVersion->supportsBcMathNumberOperatorOverloading() || $leftSide instanceof NeverType || $rightSide instanceof NeverType) {
			return false;
		}

		$bcMathNumberType = new ObjectType('BcMath\Number');

		return in_array($operatorSigil, ['-', '+', '*', '/', '**', '%'], true)
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
