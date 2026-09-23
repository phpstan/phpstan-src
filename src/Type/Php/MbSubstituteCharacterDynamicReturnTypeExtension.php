<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersions;
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

		// valid code points on every analysed PHP version
		$validCodePoints = $this->createCodePointsType($phpVersion, true);
		// valid code points on at least one analysed PHP version
		$possibleCodePoints = $this->createCodePointsType($phpVersion, false);

		if (!isset($functionCall->getArgs()[0])) {
			return TypeCombinator::union(
				new ConstantStringType('none'),
				new ConstantStringType('long'),
				new ConstantStringType('entity'),
				$possibleCodePoints,
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
			if ($validCodePoints->isSuperTypeOf($argType)->yes()) {
				return new ConstantBooleanType(true);
			}

			if ($possibleCodePoints->isSuperTypeOf($argType)->no()) {
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
					$codePoint = new ConstantIntegerType((int) $value);
					if ($validCodePoints->isSuperTypeOf($codePoint)->yes()) {
						return new ConstantBooleanType(true);
					}
					if ($possibleCodePoints->isSuperTypeOf($codePoint)->no()) {
						return new ConstantBooleanType(false);
					}

					return new BooleanType();
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

	/**
	 * @param bool $onAllVersions Whether the code points must be valid on every analysed PHP version, or on at least one
	 */
	private function createCodePointsType(PhpVersions $phpVersion, bool $onAllVersions): Type
	{
		$zeroValid = $phpVersion->isZeroValidCodePointInMbSubstituteCharacter();
		$supportsAllUnicodeScalars = $phpVersion->supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter();

		if ($onAllVersions) {
			$minCodePoint = $zeroValid->yes() ? 0 : 1;
			$maxCodePoint = $supportsAllUnicodeScalars->yes() ? 0x10FFFF : 0xFFFE;
			$excludeSurrogates = !$supportsAllUnicodeScalars->no();
		} else {
			$minCodePoint = $zeroValid->no() ? 1 : 0;
			$maxCodePoint = $supportsAllUnicodeScalars->no() ? 0xFFFE : 0x10FFFF;
			$excludeSurrogates = $supportsAllUnicodeScalars->yes();
		}

		if ($excludeSurrogates) {
			// Surrogates aren't valid in PHP 7.2+
			return TypeCombinator::union(
				IntegerRangeType::fromInterval($minCodePoint, 0xD7FF),
				IntegerRangeType::fromInterval(0xE000, $maxCodePoint),
			);
		}

		return IntegerRangeType::fromInterval($minCodePoint, $maxCodePoint);
	}

}
