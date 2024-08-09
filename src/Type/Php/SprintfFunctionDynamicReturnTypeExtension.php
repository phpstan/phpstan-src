<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\CombinationsHelper;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function array_fill;
use function array_key_exists;
use function array_shift;
use function count;
use function in_array;
use function intval;
use function is_string;
use function preg_match;
use function sprintf;
use function substr;
use function vsprintf;

final class SprintfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sprintf', 'vsprintf'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		$constantType = $this->getConstantType($args, $functionReflection, $scope);
		if ($constantType !== null) {
			return $constantType;
		}

		$formatType = $scope->getType($args[0]->value);
		$formatStrings = $formatType->getConstantStrings();

		$singlePlaceholderEarlyReturn = null;
		$allPatternsNonEmpty = count($formatStrings) !== 0;
		$allPatternsNonFalsy = count($formatStrings) !== 0;
		foreach ($formatStrings as $constantString) {
			$constantParts = $this->getFormatConstantParts(
				$constantString->getValue(),
				$functionReflection,
				$functionCall,
				$scope,
			);
			if ($constantParts !== null) {
				if ($constantParts->isNonFalsyString()->yes()) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
					// keep all bool flags as is
				} elseif ($constantParts->isNonEmptyString()->yes()) {
					$allPatternsNonFalsy = false;
				} else {
					$allPatternsNonEmpty = false;
					$allPatternsNonFalsy = false;
				}
			} else {
				$allPatternsNonEmpty = false;
				$allPatternsNonFalsy = false;
			}

			// The printf format is %[argnum$][flags][width][.precision]specifier.
			if (preg_match('/^%([0-9]*\$)?[0-9]*\.?[0-9]*([sbdeEfFgGhHouxX])$/', $constantString->getValue(), $matches) === 1) {
				if ($matches[1] !== '') {
					// invalid positional argument
					if ($matches[1] === '0$') {
						return null;
					}
					$checkArg = intval(substr($matches[1], 0, -1));
				} else {
					$checkArg = 1;
				}

				// constant string specifies a numbered argument that does not exist
				if (!array_key_exists($checkArg, $args)) {
					return null;
				}

				// if the format string is just a placeholder and specified an argument
				// of stringy type, then the return value will be of the same type
				$checkArgType = $scope->getType($args[$checkArg]->value);
				if ($matches[2] === 's'
					&& ($checkArgType->isString()->yes() || $checkArgType->isInteger()->yes())
				) {
					$singlePlaceholderEarlyReturn = $checkArgType->toString();
				} elseif ($matches[2] !== 's') {
					$singlePlaceholderEarlyReturn = new IntersectionType([
						new StringType(),
						new AccessoryNumericStringType(),
					]);
				}

				continue;
			}

			$singlePlaceholderEarlyReturn = null;
			break;
		}

		if ($singlePlaceholderEarlyReturn !== null) {
			return $singlePlaceholderEarlyReturn;
		}

		if ($allPatternsNonFalsy) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}

		$isNonEmpty = $allPatternsNonEmpty;
		if (
			!$isNonEmpty
			&& $functionReflection->getName() === 'sprintf'
			&& count($args) >= 2
			&& $formatType->isNonEmptyString()->yes()
		) {
			$allArgsNonEmpty = true;
			foreach ($args as $key => $arg) {
				if ($key === 0) {
					continue;
				}

				if (!$scope->getType($arg->value)->toString()->isNonEmptyString()->yes()) {
					$allArgsNonEmpty = false;
					break;
				}
			}

			if ($allArgsNonEmpty) {
				$isNonEmpty = true;
			}
		}

		if ($isNonEmpty) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}

		return new StringType();
	}

	/**
	 * Detect constant strings in the format which neither depend on placeholders nor on given value arguments.
	 */
	private function getFormatConstantParts(
		string $format,
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?ConstantStringType
	{
		$args = $functionCall->getArgs();
		if ($functionReflection->getName() === 'sprintf') {
			$valuesCount = count($args) - 1;
		} elseif (
			$functionReflection->getName() === 'vsprintf'
			&& count($args) >= 2
		) {
			$arraySize = $scope->getType($args[1]->value)->getArraySize();
			if (!($arraySize instanceof ConstantIntegerType)) {
				return null;
			}

			$valuesCount = $arraySize->getValue();
		} else {
			return null;
		}

		if ($valuesCount <= 0) {
			return null;
		}
		$dummyValues = array_fill(0, $valuesCount, '');

		try {
			$formatted = @vsprintf($format, $dummyValues);
			if ($formatted === false) { // @phpstan-ignore identical.alwaysFalse (PHP7.2 compat)
				return null;
			}
			return new ConstantStringType($formatted);
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * @param Arg[] $args
	 */
	private function getConstantType(array $args, FunctionReflection $functionReflection, Scope $scope): ?Type
	{
		$values = [];
		$combinationsCount = 1;
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return null;
			}

			$argType = $scope->getType($arg->value);
			$constantScalarValues = $argType->getConstantScalarValues();

			if (count($constantScalarValues) === 0) {
				if ($argType instanceof IntegerRangeType) {
					foreach ($argType->getFiniteTypes() as $finiteType) {
						$constantScalarValues[] = $finiteType->getValue();
					}
				}
			}

			if (count($constantScalarValues) === 0) {
				return null;
			}

			$values[] = $constantScalarValues;
			$combinationsCount *= count($constantScalarValues);
		}

		if ($combinationsCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		$combinations = CombinationsHelper::combinations($values);
		$returnTypes = [];
		foreach ($combinations as $combination) {
			$format = array_shift($combination);
			if (!is_string($format)) {
				return null;
			}

			try {
				if ($functionReflection->getName() === 'sprintf') {
					$returnTypes[] = $scope->getTypeFromValue(@sprintf($format, ...$combination));
				} else {
					$returnTypes[] = $scope->getTypeFromValue(@vsprintf($format, $combination));
				}
			} catch (Throwable) {
				return null;
			}
		}

		if (count($returnTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		return TypeCombinator::union(...$returnTypes);
	}

}
