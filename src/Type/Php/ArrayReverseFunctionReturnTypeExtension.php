<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

final class ArrayReverseFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reverse';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$type = $scope->getType($functionCall->getArgs()[0]->value);
		$preserveKeysType = isset($functionCall->getArgs()[1]) ? $scope->getType($functionCall->getArgs()[1]->value) : new NeverType();
		$preserveKeys = $preserveKeysType->isTrue()->yes();

		if (!$type->isArray()->yes()) {
			return null;
		}

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) > 0) {
			$results = [];
			foreach ($constantArrays as $constantArray) {
				$results[] = $constantArray->reverse($preserveKeys);
			}

			return TypeCombinator::union(...$results);
		}

		return $type;
	}

}
