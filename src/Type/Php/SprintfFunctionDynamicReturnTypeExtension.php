<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\CombinationsHelper;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function array_key_exists;
use function array_shift;
use function count;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_contains;
use function vsprintf;

class SprintfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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

		$formatType = $scope->getType($args[0]->value);
		if (count($args) === 1) {
			return $formatType;
		}

		if (
			$functionReflection->getName() === 'vsprintf'
			&& count($args) === 2
			&& $scope->getType($args[1]->value)->isIterableAtLeastOnce()->no()
		) {
			return $formatType;
		}

		$formatStrings = $formatType->getConstantStrings();
		if (count($formatStrings) === 0) {
			return null;
		}

		$isNonEmpty = false;
		$isNonFalsy = false;
		$isNumeric = false;
		foreach ($formatStrings as $constantString) {
			// The printf format is %[argnum$][flags][width][.precision]specifier.
			if (preg_match('/^(?<!%)%(?P<argnum>[0-9]*\$)?(?P<width>[0-9])*\.?[0-9]*(?P<flags>[a-zA-Z])$/', $constantString->getValue(), $matches) !== 1) {
				continue;
			}

			// invalid positional argument
			if (array_key_exists('argnum', $matches) && $matches['argnum'] === '0$') {
				return null;
			}

			if (array_key_exists('width', $matches)) {
				if ($matches['width'] > 1) {
					$isNonFalsy = true;
				} elseif ($matches['width'] > 0) {
					$isNonEmpty = true;
				}
			}

			if (array_key_exists('flags', $matches) && str_contains('bdeEfFgGhHouxX', $matches['flags'])) {
				$isNumeric = true;
				break;
			}
		}

		$argTypes = [];
		foreach ($args as $i => $arg) {
			$argType = $scope->getType($arg->value);
			$argTypes[] = $argType;

			if ($i === 0) { // skip format type
				continue;
			}

			if ($functionReflection->getName() === 'vsprintf') {
				if ($argType->isIterableAtLeastOnce()->yes()) {
					$isNonEmpty = true;
				}
				continue;
			}

			if ($argType->toString()->isNonFalsyString()->yes()) {
				$isNonFalsy = true;
			} elseif ($argType->toString()->isNonEmptyString()->yes()) {
				$isNonEmpty = true;
			}
		}

		$accessories = [];
		if ($isNumeric) {
			$accessories[] = new AccessoryNumericStringType();
		}
		if ($isNonFalsy) {
			$accessories[] = new AccessoryNonFalsyStringType();
		} elseif ($isNonEmpty) {
			$accessories[] = new AccessoryNonEmptyStringType();
		}
		$returnType = new StringType();
		if (count($accessories) > 0) {
			$accessories[] = new StringType();
			$returnType = new IntersectionType($accessories);
		}

		return $this->getConstantType($argTypes, $returnType, $functionReflection, $scope);
	}

	private function getConstantType(array $argTypes, Type $fallbackReturnType, FunctionReflection $functionReflection, Scope $scope): Type
	{
		$values = [];
		$combinationsCount = 1;
		foreach ($argTypes as $argType) {
			$constantScalarValues = $argType->getConstantScalarValues();

			if (count($constantScalarValues) === 0) {
				if ($argType instanceof IntegerRangeType) {
					foreach ($argType->getFiniteTypes() as $finiteType) {
						$constantScalarValues[] = $finiteType->getValue();
					}
				}
			}

			if (count($constantScalarValues) === 0) {
				return $fallbackReturnType;
			}

			$values[] = $constantScalarValues;
			$combinationsCount *= count($constantScalarValues);
		}

		if ($combinationsCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return $fallbackReturnType;
		}

		$combinations = CombinationsHelper::combinations($values);
		$returnTypes = [];
		foreach ($combinations as $combination) {
			$format = array_shift($combination);
			if (!is_string($format)) {
				return $fallbackReturnType;
			}

			try {
				if ($functionReflection->getName() === 'sprintf') {
					$returnTypes[] = $scope->getTypeFromValue(@sprintf($format, ...$combination));
				} else {
					$returnTypes[] = $scope->getTypeFromValue(@vsprintf($format, $combination));
				}
			} catch (Throwable) {
				return $fallbackReturnType;
			}
		}

		if (count($returnTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return $fallbackReturnType;
		}

		return TypeCombinator::union(...$returnTypes);
	}

}
