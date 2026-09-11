<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Traverser\UnsafeArrayStringKeyCastingTraverser;
use PHPStan\Type\Type;

#[AutowiredService]
final class ArrayKeyDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'key';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new NullType();
		}

		$keyType = $argType->getIterableKeyType();
		if ($iterableAtLeastOnce->yes()) {
			return UnsafeArrayStringKeyCastingTraverser::castReadKeyType($keyType);
		}

		return UnsafeArrayStringKeyCastingTraverser::unionWithReadKeyType($keyType, new NullType());
	}

}
