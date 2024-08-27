<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;
use const COUNT_RECURSIVE;

final class CountFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sizeof', 'count'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		if (count($functionCall->getArgs()) > 1) {
			$mode = $scope->getType($functionCall->getArgs()[1]->value);
			if ($mode->isSuperTypeOf(new ConstantIntegerType(COUNT_RECURSIVE))->yes()) {
				return null;
			}
		}

		return $scope->getType($functionCall->getArgs()[0]->value)->getArraySize();
	}

}
