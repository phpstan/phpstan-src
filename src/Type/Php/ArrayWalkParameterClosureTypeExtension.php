<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

#[AutowiredService]
final class ArrayWalkParameterClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'array_walk' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		$parameters = [
			new NativeParameterReflection('item', false, $scope->getIterableValueType($arrayType), PassedByReference::createReadsArgument(), false, null),
			new NativeParameterReflection('key', false, $scope->getIterableKeyType($arrayType), PassedByReference::createNo(), false, null),
		];
		if (isset($args[2])) {
			$parameters[] = new NativeParameterReflection('arg', false, $scope->getType($args[2]->value), PassedByReference::createNo(), false, null);
		}

		return new ClosureType($parameters, new MixedType());
	}

}
