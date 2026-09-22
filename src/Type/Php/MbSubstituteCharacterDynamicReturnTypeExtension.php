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
		$phpVersions = $scope->getPhpVersion();
		$supportsAllUnicodeScalarCodePoints = $phpVersions->supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter();
		// The empty string is a valid alias for "none" in PHP < 8, where 0 is not a valid code point.
		$isEmptyStringValidAlias = $phpVersions->isEmptyStringValidAliasForNoneInMbSubstituteCharacter();

		// Code points valid on every PHP version the analysed range spans...
		$ranges = self::codePointRanges(
			$isEmptyStringValidAlias->no() ? 0 : 1,
			$supportsAllUnicodeScalarCodePoints->yes() ? 0x10FFFF : 0xFFFE,
			!$supportsAllUnicodeScalarCodePoints->no(),
		);
		// ...and those valid on at least one of them.
		$possibleRanges = self::codePointRanges(
			$isEmptyStringValidAlias->yes() ? 1 : 0,
			$supportsAllUnicodeScalarCodePoints->no() ? 0xFFFE : 0x10FFFF,
			$supportsAllUnicodeScalarCodePoints->yes(),
		);

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
			return PhpVersions::pickType(
				$phpVersions->throwsTypeErrorForInternalFunctions(),
				new NeverType(),
				new BooleanType(),
			);
		}

		if ($isInteger->yes()) {
			foreach ($ranges as $range) {
				if ($range->isSuperTypeOf($argType)->yes()) {
					return new ConstantBooleanType(true);
				}
			}

			if (!self::isPossiblyInRanges($possibleRanges, $argType)) {
				return PhpVersions::pickType(
					$phpVersions->throwsValueErrorForInternalFunctions(),
					new NeverType(),
					new ConstantBooleanType(false),
				);
			}
		} elseif ($isString->yes()) {
			if ($argType->isNonEmptyString()->no()) {
				return PhpVersions::pickType($isEmptyStringValidAlias, new ConstantBooleanType(true), new NeverType());
			}

			if ($phpVersions->isNumericStringValidArgInMbSubstituteCharacter()->no() && $argType->isNumericString()->yes()) {
				return new NeverType();
			}

			if ($argType instanceof ConstantStringType) {
				$value = strtolower($argType->getValue());

				if (in_array($value, ['none', 'long', 'entity'], true)) {
					return new ConstantBooleanType(true);
				}

				if ($argType->isNumericString()->yes()) {
					$codePoint = new ConstantIntegerType((int) $value);

					foreach ($ranges as $range) {
						if ($range->isSuperTypeOf($codePoint)->yes()) {
							return new ConstantBooleanType(true);
						}
					}

					if (!self::isPossiblyInRanges($possibleRanges, $codePoint)) {
						return new ConstantBooleanType(false);
					}

					return new BooleanType();
				}

				return PhpVersions::pickType(
					$phpVersions->throwsValueErrorForInternalFunctions(),
					new NeverType(),
					new ConstantBooleanType(false),
				);
			}
		} elseif ($isNull->yes()) {
			// The $substitute_character arg is nullable in PHP 8+
			return PhpVersions::pickType(
				$phpVersions->isNullValidArgInMbSubstituteCharacter(),
				new ConstantBooleanType(true),
				new ConstantBooleanType(false),
			);
		}

		return new BooleanType();
	}

	/**
	 * @return Type[]
	 */
	private static function codePointRanges(int $minCodePoint, int $maxCodePoint, bool $excludeSurrogates): array
	{
		if ($excludeSurrogates) {
			// Surrogates aren't valid in PHP 7.2+
			return [
				IntegerRangeType::fromInterval($minCodePoint, 0xD7FF),
				IntegerRangeType::fromInterval(0xE000, $maxCodePoint),
			];
		}

		return [IntegerRangeType::fromInterval($minCodePoint, $maxCodePoint)];
	}

	/**
	 * @param Type[] $ranges
	 */
	private static function isPossiblyInRanges(array $ranges, Type $type): bool
	{
		foreach ($ranges as $range) {
			if (!$range->isSuperTypeOf($type)->no()) {
				return true;
			}
		}

		return false;
	}

}
