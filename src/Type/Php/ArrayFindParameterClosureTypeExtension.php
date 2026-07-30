<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class ArrayFindParameterClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return in_array($functionReflection->getName(), ['array_find', 'array_find_key', 'array_any', 'array_all'], true)
			&& $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);

		return new ClosureType([
			new NativeParameterReflection('value', false, $scope->getIterableValueType($arrayType), PassedByReference::createNo(), false, null),
			new NativeParameterReflection('key', false, $scope->getIterableKeyType($arrayType), PassedByReference::createNo(), false, null),
		], new BooleanType());
	}

}
