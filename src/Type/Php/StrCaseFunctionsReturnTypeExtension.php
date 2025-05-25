<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_diff;
use function array_map;
use function count;
use function in_array;
use function is_callable;
use function mb_check_encoding;
use const MB_CASE_LOWER;
use const MB_CASE_UPPER;

#[AutowiredService]
final class StrCaseFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/**
	 * [function name => minimum arity]
	 */
	private const FUNCTIONS = [
		'strtoupper' => 1,
		'strtolower' => 1,
		'mb_strtoupper' => 1,
		'mb_strtolower' => 1,
		'lcfirst' => 1,
		'ucfirst' => 1,
		'mb_lcfirst' => 1,
		'mb_ucfirst' => 1,
		'ucwords' => 1,
		'mb_convert_case' => 2,
		'mb_convert_kana' => 1,
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return isset(self::FUNCTIONS[$functionReflection->getName()]);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$fnName = $functionReflection->getName();
		$args = $functionCall->getArgs();

		if (count($args) < self::FUNCTIONS[$fnName]) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		if (!is_callable($fnName)) {
			return null;
		}

		$modes = [];
		$keepLowercase = false;
		$forceLowercase = false;
		$keepUppercase = false;
		$forceUppercase = false;

		if ($fnName === 'mb_convert_case') {
			$modeType = $scope->getType($args[1]->value);
			$modes = array_map(static fn ($mode) => $mode->getValue(), TypeUtils::getConstantIntegers($modeType));
			if (count($modes) > 0) {
				$forceLowercase = count(array_diff($modes, [
					MB_CASE_LOWER,
					5, // MB_CASE_LOWER_SIMPLE
				])) === 0;
				$keepLowercase = count(array_diff($modes, [
					MB_CASE_LOWER,
					5, // MB_CASE_LOWER_SIMPLE
					3, // MB_CASE_FOLD,
					7, // MB_CASE_FOLD_SIMPLE
				])) === 0;
				$forceUppercase = count(array_diff($modes, [
					MB_CASE_UPPER,
					4, // MB_CASE_UPPER_SIMPLE
				])) === 0;
				$keepUppercase = count(array_diff($modes, [
					MB_CASE_UPPER,
					4, // MB_CASE_UPPER_SIMPLE
					3, // MB_CASE_FOLD,
					7, // MB_CASE_FOLD_SIMPLE
				])) === 0;
			}
		} elseif (in_array($fnName, ['ucwords', 'mb_convert_kana'], true)) {
			if (count($args) >= 2) {
				$modeType = $scope->getType($args[1]->value);
				$modes = array_map(static fn ($mode) => $mode->getValue(), $modeType->getConstantStrings());
			} else {
				$modes = $fnName === 'mb_convert_kana' ? ['KV'] : [" \t\r\n\f\v"];
			}
		} elseif (in_array($fnName, ['strtolower', 'mb_strtolower'], true)) {
			$forceLowercase = true;
		} elseif (in_array($fnName, ['lcfirst', 'mb_lcfirst'], true)) {
			$keepLowercase = true;
		} elseif (in_array($fnName, ['strtoupper', 'mb_strtoupper'], true)) {
			$forceUppercase = true;
		} elseif (in_array($fnName, ['ucfirst', 'mb_ucfirst'], true)) {
			$keepUppercase = true;
		}

		$constantStrings = array_map(static fn ($type) => $type->getValue(), $argType->getConstantStrings());
		if (count($constantStrings) > 0 && mb_check_encoding($constantStrings, 'UTF-8')) {
			$strings = [];

			$parameters = [];
			if (in_array($fnName, ['ucwords', 'mb_convert_case', 'mb_convert_kana'], true)) {
				foreach ($modes as $mode) {
					foreach ($constantStrings as $constantString) {
						$parameters[] = [$constantString, $mode];
					}
				}
			} else {
				$parameters = array_map(static fn ($s) => [$s], $constantStrings);
			}

			foreach ($parameters as $parameter) {
				$strings[] = $fnName(...$parameter);
			}

			if (count($strings) !== 0 && mb_check_encoding($strings, 'UTF-8')) {
				return TypeCombinator::union(...array_map(static fn ($s) => new ConstantStringType($s), $strings));
			}
		}

		$accessoryTypes = [];
		$argStringType = $argType->toString();
		if ($forceLowercase || ($keepLowercase && $argStringType->isLowercaseString()->yes())) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}
		if ($forceUppercase || ($keepUppercase && $argStringType->isUppercaseString()->yes())) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		if ($argStringType->isNumericString()->yes()) {
			$accessoryTypes[] = new AccessoryNumericStringType();
		} elseif ($argStringType->isNonFalsyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($argStringType->isNonEmptyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();

			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

}
