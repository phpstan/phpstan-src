<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

class RoundFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
			true
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (PHP_VERSION_ID >= 80000) {
			// PHP 8 fatals with a missing parameter.
			$noArgsReturnType = new NeverType(true);
			// PHP 7 can return either a float or fatal.
			$defaultReturnType = new BenevolentUnionType([
				new FloatType(),
				new NeverType(true),
			]);
		} else {
			// PHP 7 returns null with a missing parameter.
			$noArgsReturnType = new NullType();
			// PHP 7 can return either a float or false.
			$defaultReturnType = new BenevolentUnionType([
				new FloatType(),
				new ConstantBooleanType(false),
			]);
		}

		if (count($functionCall->getArgs()) < 1) {
			return $noArgsReturnType;
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);

		if ($firstArgType instanceof MixedType) {
			return $defaultReturnType;
		}

		if (PHP_VERSION_ID >= 80000) {
			if (!($firstArgType instanceof IntegerType) && !($firstArgType instanceof FloatType)) {
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
