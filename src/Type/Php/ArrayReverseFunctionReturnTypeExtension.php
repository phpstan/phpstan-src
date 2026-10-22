<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

#[AutowiredService]
final class ArrayReverseFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reverse';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$type = $scope->getType($args[0]->value);
		if ($type->isArray()->no()) {
			if ($scope->getPhpVersion()->arrayFunctionsReturnNullWithNonArray()->no()) {
				return new NeverType();
			}

			return new NullType();
		}

		$preserveKeysType = isset($args[1]) ? $scope->getType($args[1]->value) : new ConstantBooleanType(false);

		return $type->reverseArray($preserveKeysType->isTrue());
	}

}
