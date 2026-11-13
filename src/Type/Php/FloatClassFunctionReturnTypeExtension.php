<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

#[AutowiredService]
final class FloatClassFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return FloatClassFunctionTypeSpecifyingExtension::getRuledOutTypes($functionReflection->getName()) !== null;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$ruledOutTypes = FloatClassFunctionTypeSpecifyingExtension::getRuledOutTypes($functionReflection->getName());
		$args = $functionCall->getArgs();
		if (!isset($args[0]) || $ruledOutTypes === null) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		if ($ruledOutTypes[0]->isSuperTypeOf($argType)->yes()) {
			return new ConstantBooleanType(false);
		}
		if ($ruledOutTypes[1]->isSuperTypeOf($argType)->yes()) {
			return new ConstantBooleanType(true);
		}

		return new BooleanType();
	}

}
