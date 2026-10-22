<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function in_array;
use function strtolower;

#[AutowiredService]
final class MbSubstituteCharacterDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'mb_substitute_character';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$phpVersion = $scope->getPhpVersion();
		$minCodePoint = $phpVersion->isZeroValidCodePointInMbSubstituteCharacter()->yes() ? 0 : 1;
		$maxCodePoint = $phpVersion->supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter()->yes() ? 0x10FFFF : 0xFFFE;
		$ranges = [];

		if ($phpVersion->supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter()->yes()) {
			// Surrogates aren't valid in PHP 7.2+
			$ranges[] = IntegerRangeType::fromInterval($minCodePoint, 0xD7FF);
			$ranges[] = IntegerRangeType::fromInterval(0xE000, $maxCodePoint);
		} else {
			$ranges[] = IntegerRangeType::fromInterval($minCodePoint, $maxCodePoint);
		}

		if (!isset($functionCall->getArgs()[0])) {
			return TypeCombinator::union(
				new ConstantStringType('none'),
				new ConstantStringType('long'),
				new ConstantStringType('entity'),
				...$ranges,
			);
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$isString = $argType->isString();
		$isNull = $argType->isNull();
		$isInteger = $argType->isInteger();

		if ($isString->no() && $isNull->no() && $isInteger->no()) {
			if ($phpVersion->throwsTypeErrorForInternalFunctions()->yes()) {
				return new NeverType();
			}

			return new BooleanType();
		}

		if ($isInteger->yes()) {
			$invalidRanges = [];

			foreach ($ranges as $range) {
				$isInRange = $range->isSuperTypeOf($argType);

				if ($isInRange->yes()) {
					return new ConstantBooleanType(true);
				}

				$invalidRanges[] = $isInRange->no();
			}

			if ($argType instanceof ConstantIntegerType || !in_array(false, $invalidRanges, true)) {
				if ($phpVersion->throwsValueErrorForInternalFunctions()->yes()) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}
		} elseif ($isString->yes()) {
			if ($argType->isNonEmptyString()->no()) {
				// The empty string was a valid alias for "none" in PHP < 8.
				if (!$phpVersion->isEmptyStringValidAliasForNoneInMbSubstituteCharacter()->no()) {
					return new ConstantBooleanType(true);
				}

				return new NeverType();
			}

			if ($phpVersion->isNumericStringValidArgInMbSubstituteCharacter()->no() && $argType->isNumericString()->yes()) {
				return new NeverType();
			}

			if ($argType instanceof ConstantStringType) {
				$value = strtolower($argType->getValue());

				if (in_array($value, ['none', 'long', 'entity'], true)) {
					return new ConstantBooleanType(true);
				}

				if ($argType->isNumericString()->yes()) {
					$codePoint = (int) $value;
					$isValid = $codePoint >= $minCodePoint && $codePoint <= $maxCodePoint;

					if ($phpVersion->supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter()->yes()) {
						$isValid = $isValid && ($codePoint < 0xD800 || $codePoint > 0xDFFF);
					}

					return new ConstantBooleanType($isValid);
				}

				if ($phpVersion->throwsValueErrorForInternalFunctions()->yes()) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}
		} elseif ($isNull->yes()) {
			// The $substitute_character arg is nullable in PHP 8+
			return $phpVersion->isNullValidArgInMbSubstituteCharacter()->toBooleanType();
		}

		return new BooleanType();
	}

}
