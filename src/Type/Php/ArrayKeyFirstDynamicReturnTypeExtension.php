<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

class ArrayKeyFirstDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_key_first';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new NullType();
		}

		$constantArrays = TypeUtils::getOldConstantArrays($argType);
		if (count($constantArrays) > 0) {
			$keyTypes = [];
			foreach ($constantArrays as $constantArray) {
				if ($constantArray->isEmpty()) {
					$keyTypes[] = new NullType();
					continue;
				}

				$keyTypes[] = $constantArray->getFirstKeyType();
			}

			return TypeCombinator::union(...$keyTypes);
		}

		$keyType = $argType->getIterableKeyType();
		if ($iterableAtLeastOnce->yes()) {
			return $keyType;
		}

		return TypeCombinator::union($keyType, new NullType());
	}

}
