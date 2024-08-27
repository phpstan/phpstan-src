<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class ArrayPointerFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var string[] */
	private array $functions = [
		'reset',
		'end',
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), $this->functions, true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return null;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new ConstantBooleanType(false);
		}

		$itemType = $functionReflection->getName() === 'reset'
			? $argType->getFirstIterableValueType()
			: $argType->getLastIterableValueType();
		if ($iterableAtLeastOnce->yes()) {
			return $itemType;
		}

		return TypeCombinator::union($itemType, new ConstantBooleanType(false));
	}

}
