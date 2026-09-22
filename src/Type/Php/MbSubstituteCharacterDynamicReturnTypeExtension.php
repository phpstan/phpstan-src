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
		$phpVersions = $scope->getPhpVersion();
		$supportsAllCodePoints = $phpVersions->supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter();
		$isPhp8 = $phpVersions->throwsValueErrorForInternalFunctions();

		// mb_substitute_character() behaves differently on both sides of the PHP 7.2 and the PHP 8.0
		// boundary. When the analysed version range spans one of them, both behaviours are possible.
		$results = [];
		foreach ([true, false] as $supportsAllCodePointsValue) {
			if ($supportsAllCodePointsValue ? $supportsAllCodePoints->no() : $supportsAllCodePoints->yes()) {
				continue;
			}
			foreach ([true, false] as $isPhp8Value) {
				if ($isPhp8Value ? $isPhp8->no() : $isPhp8->yes()) {
					continue;
				}
				if ($isPhp8Value && !$supportsAllCodePointsValue) {
					// PHP 8 always supports all unicode scalar code points.
					continue;
				}

				$results[] = $this->resolveType($functionCall, $scope, $supportsAllCodePointsValue, $isPhp8Value);
			}
		}

		return TypeCombinator::union(...$results);
	}

	private function resolveType(FuncCall $functionCall, Scope $scope, bool $supportsAllCodePoints, bool $isPhp8): Type
	{
		$minCodePoint = $isPhp8 ? 0 : 1;
		$maxCodePoint = $supportsAllCodePoints ? 0x10FFFF : 0xFFFE;
		$ranges = [];

		if ($supportsAllCodePoints) {
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
			if ($isPhp8) {
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
				if ($isPhp8) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}
		} elseif ($isString->yes()) {
			if ($argType->isNonEmptyString()->no()) {
				// The empty string was a valid alias for "none" in PHP < 8.
				if (!$isPhp8) {
					return new ConstantBooleanType(true);
				}

				return new NeverType();
			}

			if ($isPhp8 && $argType->isNumericString()->yes()) {
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

					if ($supportsAllCodePoints) {
						$isValid = $isValid && ($codePoint < 0xD800 || $codePoint > 0xDFFF);
					}

					return new ConstantBooleanType($isValid);
				}

				if ($isPhp8) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}
		} elseif ($isNull->yes()) {
			// The $substitute_character arg is nullable in PHP 8+
			return new ConstantBooleanType($isPhp8);
		}

		return new BooleanType();
	}

}
