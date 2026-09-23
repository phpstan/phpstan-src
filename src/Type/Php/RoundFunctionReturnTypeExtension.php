<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;
use function in_array;

#[AutowiredService]
final class RoundFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(
			$functionReflection->getName(),
			[
				'round',
				'ceil',
				'floor',
			],
			true,
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		// PHP 7 can return either a float or false.
		// PHP 8 can either return a float or fatal.
		$defaultReturnType = null;

		$hasStricterRoundFunctions = $scope->getPhpVersion()->hasStricterRoundFunctions();

		if (count($functionCall->getArgs()) < 1) {
			// PHP 8 fatals with a missing parameter, PHP 7 returns null.
			if ($hasStricterRoundFunctions->yes()) {
				return new NeverType(true);
			}

			return new NullType();
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);

		if ($firstArgType instanceof MixedType) {
			return $defaultReturnType;
		}

		if ($hasStricterRoundFunctions->yes()) {
			if (!$scope->isDeclareStrictTypes()) {
				$allowed = new UnionType([
					new IntegerType(),
					new FloatType(),
					new IntersectionType([
						new StringType(),
						new AccessoryNumericStringType(),
					]),
					new NullType(),
					new BooleanType(),
				]);
			} else {
				$allowed = new UnionType([
					new IntegerType(),
					new FloatType(),
				]);
			}

			if ($allowed->isSuperTypeOf($firstArgType)->no()) {
				// PHP 8 fatals if the parameter is not an integer or float.
				return new NeverType(true);
			}
		} elseif ($firstArgType->isArray()->yes()) {
			// PHP 7 returns false if the parameter is an array.
			return new ConstantBooleanType(false);
		}

		return new FloatType();
	}

}
