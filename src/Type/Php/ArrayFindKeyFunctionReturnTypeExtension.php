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
use function count;

#[AutowiredService]
final class ArrayFindKeyFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_find_key';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if (count($arrayType->getArrays()) < 1) {
			return null;
		}

		return UnsafeArrayStringKeyCastingTraverser::unionWithReadKeyType($arrayType->getIterableKeyType(), new NullType());
	}

}
