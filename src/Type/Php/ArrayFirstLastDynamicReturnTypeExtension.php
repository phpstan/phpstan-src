<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

#[AutowiredService]
final class ArrayFirstLastDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'array_key_first',
			'array_key_last',
			'array_first',
			'array_last',
		], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 1) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();

		if ($iterableAtLeastOnce->no()) {
			return new NullType();
		}

		switch ($functionReflection->getName()) {
			case 'array_key_first':
			case 'array_key_last':
				$resultType = $argType->getIterableKeyType();
				break;
			case 'array_first':
			case 'array_last':
				$resultType = $argType->getIterableValueType();
				break;
			default:
				throw new ShouldNotHappenException();
		}

		if ($iterableAtLeastOnce->yes()) {
			return $resultType;
		}

		return TypeCombinator::union($resultType, new NullType());
	}

}
