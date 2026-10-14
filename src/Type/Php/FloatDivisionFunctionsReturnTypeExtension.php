<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function fdiv;
use function fmod;
use function in_array;
use function is_finite;

/**
 * Constant-folds the function forms of `/` and `%` that operate on floats. Both are left
 * to their signature type as soon as the result would be INF or NAN, which no constant
 * float type can represent.
 */
#[AutowiredService]
final class FloatDivisionFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['fdiv', 'fmod'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$numerators = $scope->getType($args[0]->value)->toFloat()->getConstantScalarValues();
		$divisors = $scope->getType($args[1]->value)->toFloat()->getConstantScalarValues();
		if ($numerators === [] || $divisors === []) {
			return null;
		}

		if (count($numerators) * count($divisors) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		$isFdiv = $functionReflection->getName() === 'fdiv';
		$resultTypes = [];
		foreach ($numerators as $numerator) {
			foreach ($divisors as $divisor) {
				$result = $isFdiv ? fdiv((float) $numerator, (float) $divisor) : fmod((float) $numerator, (float) $divisor);
				if (!is_finite($result)) {
					return null;
				}

				$resultTypes[] = new ConstantFloatType($result);
			}
		}

		return TypeCombinator::union(...$resultTypes);
	}

}
