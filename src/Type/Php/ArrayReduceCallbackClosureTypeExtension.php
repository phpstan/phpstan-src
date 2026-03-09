<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

#[AutowiredService]
final class ArrayReduceCallbackClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'array_reduce' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (!isset($args[0])) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		$valueType = $arrayType->getIterableValueType();

		if (isset($args[2])) {
			$initialType = $scope->getType($args[2]->value);
		} else {
			$initialType = new NullType();
		}

		$carryType = $initialType->generalize(GeneralizePrecision::templateArgument());

		return new ClosureType(
			[
				new NativeParameterReflection('carry', false, $carryType, $parameter->passedByReference(), false, null),
				new NativeParameterReflection('value', false, $valueType, $parameter->passedByReference(), false, null),
			],
			new MixedType(),
		);
	}

}
