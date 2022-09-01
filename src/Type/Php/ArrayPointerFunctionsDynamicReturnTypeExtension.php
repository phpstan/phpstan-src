<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;
use function in_array;

class ArrayPointerFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new ConstantBooleanType(false);
		}

		$constantArrays = TypeUtils::getOldConstantArrays($argType);
		if (count($constantArrays) > 0) {
			$keyTypes = [];
			foreach ($constantArrays as $constantArray) {
				if ($constantArray->isEmpty()) {
					$keyTypes[] = new ConstantBooleanType(false);
					continue;
				}

				$keyTypes[] = $functionReflection->getName() === 'reset'
					? $constantArray->getFirstValueType()
					: $constantArray->getLastValueType();
			}

			return TypeCombinator::union(...$keyTypes);
		}

		$itemType = $argType->getIterableValueType();
		if ($iterableAtLeastOnce->yes()) {
			return $itemType;
		}

		return TypeCombinator::union($itemType, new ConstantBooleanType(false));
	}

}
