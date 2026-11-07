<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class ArrayReduceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private ArrayReduceCarryTypeResolver $carryTypeResolver,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reduce';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[1])) {
			return null;
		}

		$resolvedCarry = $this->carryTypeResolver->resolve($functionCall, $scope);
		if ($resolvedCarry !== null) {
			$callbackReturnType = $resolvedCarry['return'];
		} else {
			$callbackType = $scope->getType($args[1]->value);
			if ($callbackType->isCallable()->no()) {
				return null;
			}

			$callbackReturnType = ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$args,
				$callbackType->getCallableParametersAcceptors($scope),
			)->getReturnType();
		}

		if (isset($args[2])) {
			$initialType = $scope->getType($args[2]->value);
		} else {
			$initialType = new NullType();
		}

		// array_reduce() throws on anything but an array, so only the arrays
		// it is given decide whether the initial value can come back
		$arrayType = TypeCombinator::intersect($scope->getType($args[0]->value), new ArrayType(new MixedType(), new MixedType()));
		$iterableAtLeastOnce = $arrayType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return $initialType;
		}
		if ($iterableAtLeastOnce->yes()) {
			return $callbackReturnType;
		}

		return TypeCombinator::union($callbackReturnType, $initialType);
	}

}
