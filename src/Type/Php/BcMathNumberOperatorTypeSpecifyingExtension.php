<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class BcMathNumberOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{

	private ObjectType $mathNumberType;

	public function __construct(private PhpVersion $phpVersion)
	{
		$this->mathNumberType = new ObjectType('BcMath\Number');
	}

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		if (!$this->phpVersion->supportsBcMathNumberOperatorOverloading() || $leftSide instanceof NeverType || $rightSide instanceof NeverType) {
			return false;
		}

		return in_array($operatorSigil, ['-', '+', '*', '/', '**', '%', '<', '<=', '>', '>=', '==', '!=', '<=>'], true)
			&& (
				$this->mathNumberType->isSuperTypeOf($leftSide)->yes()
				|| $this->mathNumberType->isSuperTypeOf($rightSide)->yes()
			);
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		$otherSide = $this->mathNumberType->isSuperTypeOf($leftSide)->yes()
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
			|| $this->mathNumberType->isSuperTypeOf($otherSide)->yes()
		) {
			return $this->mathNumberType;
		}

		return new ErrorType();
	}

}
