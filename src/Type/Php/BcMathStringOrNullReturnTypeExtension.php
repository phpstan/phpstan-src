<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\UnaryMinus;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function in_array;
use function is_numeric;

#[AutowiredService]
final class BcMathStringOrNullReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['bcdiv', 'bcmod', 'bcpowmod', 'bcsqrt'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if ($functionReflection->getName() === 'bcsqrt') {
			return $this->getTypeForBcSqrt($functionCall, $scope);
		}

		if ($functionReflection->getName() === 'bcpowmod') {
			return $this->getTypeForBcPowMod($functionCall, $scope);
		}

		$throwsTypeError = $scope->getPhpVersion()->throwsTypeErrorForInternalFunctions();
		$stringAndNumericStringType = new IntersectionType([new StringType(), new AccessoryNumericStringType()]);

		$args = $functionCall->getArgs();
		if (isset($args[1]) === false) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new NullType();
		}

		if ($throwsTypeError->yes()) {
			$defaultReturnType = $stringAndNumericStringType;
		} else {
			$defaultReturnType = new UnionType([$stringAndNumericStringType, new NullType()]);
		}

		$secondArgument = $scope->getType($args[1]->value);
		$secondArgumentIsNumeric = ($secondArgument instanceof ConstantScalarType && is_numeric($secondArgument->getValue())) || $secondArgument->isInteger()->yes();

		if ($secondArgument instanceof ConstantScalarType && ($this->isZero($secondArgument->getValue()) || !$secondArgumentIsNumeric)) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new NullType();
		}

		if (isset($args[2]) === false) {
			if ($secondArgument instanceof ConstantScalarType || $secondArgumentIsNumeric) {
				return $stringAndNumericStringType;
			}

			return $defaultReturnType;
		}

		$thirdArgument = $scope->getType($args[2]->value);
		$thirdArgumentIsNumeric = false;
		$thirdArgumentIsNegative = false;
		if ($thirdArgument instanceof ConstantScalarType && is_numeric($thirdArgument->getValue())) {
			$thirdArgumentIsNumeric = true;
			$thirdArgumentIsNegative = ($thirdArgument->getValue() < 0);
		} elseif ($thirdArgument->isInteger()->yes()) {
			$thirdArgumentIsNumeric = true;
			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($thirdArgument)->yes()) {
				$thirdArgumentIsNegative = true;
			}
		}

		if ($thirdArgument instanceof ConstantScalarType && !is_numeric($thirdArgument->getValue())) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new NullType();
		}

		if ($throwsTypeError->yes() && $thirdArgumentIsNegative) {
			return new NeverType();
		}

		if (($secondArgument instanceof ConstantScalarType || $secondArgumentIsNumeric) && $thirdArgumentIsNumeric) {
			return $stringAndNumericStringType;
		}

		return $defaultReturnType;
	}

	/**
	 * bcsqrt
	 * https://www.php.net/manual/en/function.bcsqrt.php
	 * > Returns the square root as a string, or NULL if operand is negative.
	 *
	 */
	private function getTypeForBcSqrt(FuncCall $functionCall, Scope $scope): Type
	{
		$throwsTypeError = $scope->getPhpVersion()->throwsTypeErrorForInternalFunctions();
		$stringAndNumericStringType = new IntersectionType([new StringType(), new AccessoryNumericStringType()]);
		if ($throwsTypeError->yes()) {
			$defaultReturnType = $stringAndNumericStringType;
		} else {
			$defaultReturnType = new UnionType([$stringAndNumericStringType, new NullType()]);
		}

		$args = $functionCall->getArgs();
		if (isset($args[0]) === false) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return $defaultReturnType;
		}

		$firstArgument = $scope->getType($args[0]->value);

		$firstArgumentIsPositive = $firstArgument instanceof ConstantScalarType && is_numeric($firstArgument->getValue()) && $firstArgument->getValue() >= 0;
		$firstArgumentIsNegative = $firstArgument instanceof ConstantScalarType && is_numeric($firstArgument->getValue()) && $firstArgument->getValue() < 0;

		if ($firstArgument instanceof UnaryMinus || $firstArgumentIsNegative) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new NullType();
		}

		if (isset($args[1]) === false) {
			if ($firstArgumentIsPositive) {
				return $stringAndNumericStringType;
			}

			return $defaultReturnType;
		}

		$secondArgument = $scope->getType($args[1]->value);
		$secondArgumentIsValid = $secondArgument instanceof ConstantScalarType && is_numeric($secondArgument->getValue()) && !$this->isZero($secondArgument->getValue());
		$secondArgumentIsNonNumeric = $secondArgument instanceof ConstantScalarType && !is_numeric($secondArgument->getValue());
		$secondArgumentIsNegative = $secondArgument instanceof ConstantScalarType && is_numeric($secondArgument->getValue()) && $secondArgument->getValue() < 0;

		if ($secondArgumentIsNonNumeric) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new NullType();
		}

		if ($secondArgument instanceof UnaryMinus || $secondArgumentIsNegative) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}
		}

		if ($firstArgumentIsPositive && $secondArgumentIsValid) {
			return $stringAndNumericStringType;
		}

		return $defaultReturnType;
	}

	/**
	 * bcpowmod()
	 * https://www.php.net/manual/en/function.bcpowmod.php
	 * > Returns the result as a string, or FALSE if modulus is 0 or exponent is negative.
	 */
	private function getTypeForBcPowMod(FuncCall $functionCall, Scope $scope): Type
	{
		$throwsTypeError = $scope->getPhpVersion()->throwsTypeErrorForInternalFunctions();
		$args = $functionCall->getArgs();
		if ($throwsTypeError->yes() && isset($args[0]) === false) {
			return new NeverType();
		}

		$stringAndNumericStringType = new IntersectionType([new StringType(), new AccessoryNumericStringType()]);

		if (isset($args[1]) === false) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new UnionType([$stringAndNumericStringType, new ConstantBooleanType(false)]);
		}

		$exponent = $scope->getType($args[1]->value);

		// Expontent is non numeric
		if ($throwsTypeError->yes()
			&& $exponent instanceof ConstantScalarType && !is_numeric($exponent->getValue())
		) {
			return new NeverType();
		}

		$exponentIsNegative = IntegerRangeType::fromInterval(null, 0)->isSuperTypeOf($exponent)->yes();

		if ($exponent instanceof ConstantScalarType) {
			$exponentIsNegative = is_numeric($exponent->getValue()) && $exponent->getValue() < 0;
		}

		if ($exponentIsNegative) {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}

			return new ConstantBooleanType(false);
		}

		if (isset($args[2])) {
			$modulus = $scope->getType($args[2]->value);
			$modulusIsZero = $modulus instanceof ConstantScalarType && $this->isZero($modulus->getValue());
			$modulusIsNonNumeric = $modulus instanceof ConstantScalarType && !is_numeric($modulus->getValue());

			if ($modulusIsZero || $modulusIsNonNumeric) {
				if ($throwsTypeError->yes()) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}

			if ($modulus instanceof ConstantScalarType) {
				return $stringAndNumericStringType;
			}
		} else {
			if ($throwsTypeError->yes()) {
				return new NeverType();
			}
		}

		if ($throwsTypeError->yes()) {
			return $stringAndNumericStringType;
		}

		return new UnionType([$stringAndNumericStringType, new ConstantBooleanType(false)]);
	}

	/**
	 * Utility to help us determine if value is zero. Handles cases where we pass "0.000" too.
	 *
	 * @param mixed $value
	 */
	private function isZero($value): bool
	{
		if (is_numeric($value) === false) {
			return false;
		}

		if ($value > 0 || $value < 0) {
			return false;
		}

		return true;
	}

}
