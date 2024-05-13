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
use PHPStan\Type\Constant\ConstantIntegerType;
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
		if (count($formatType->getConstantStrings()) > 0) {
			$skip = false;
			foreach ($formatType->getConstantStrings() as $constantString) {
				// The printf format is %[argnum$][flags][width][.precision]
				if (preg_match('/^%([0-9]*\$)?[0-9]*\.?[0-9]*[bdeEfFgGhHouxX]$/', $constantString->getValue(), $matches) === 1) {
					// invalid positional argument
					if (array_key_exists(1, $matches) && $matches[1] === '0$') {
						return null;
					}

					continue;
				}

				$skip = true;
				break;
			}

			if (!$skip) {
				return new IntersectionType([
					new StringType(),
					new AccessoryNumericStringType(),
				]);
			}
		}

		if ($formatType->isNonFalsyString()->yes()) {
			$returnType = new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		} elseif ($formatType->isNonEmptyString()->yes()) {
			$returnType = new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		} else {
			$returnType = new StringType();
		}

		$values = [];
		$combinationsCount = 1;
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			$constantScalarValues = $argType->getConstantScalarValues();

			if (count($constantScalarValues) === 0) {
				if ($argType instanceof IntegerRangeType) {
					foreach ($argType->getFiniteTypes() as $finiteType) {
						if (!$finiteType instanceof ConstantIntegerType) {
							throw new ShouldNotHappenException();
						}
						$constantScalarValues[] = $finiteType->getValue();
					}
				}
			}

			if (count($constantScalarValues) === 0) {
				return $returnType;
			}

			$values[] = $constantScalarValues;
			$combinationsCount *= count($constantScalarValues);
		}

		if ($combinationsCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return $returnType;
		}

		$combinations = CombinationsHelper::combinations($values);
		$returnTypes = [];
		foreach ($combinations as $combination) {
			$format = array_shift($combination);
			if (!is_string($format)) {
				return $returnType;
			}

			try {
				if ($functionReflection->getName() === 'sprintf') {
					$returnTypes[] = $scope->getTypeFromValue(@sprintf($format, ...$combination));
				} else {
					$returnTypes[] = $scope->getTypeFromValue(@vsprintf($format, $combination));
				}
			} catch (Throwable) {
				return $returnType;
			}
		}

		if (count($returnTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return $returnType;
		}

		return TypeCombinator::union(...$returnTypes);
	}

}
