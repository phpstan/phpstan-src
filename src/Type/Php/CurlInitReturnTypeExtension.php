<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class CurlInitReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'curl_init';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		\PhpParser\Node\Expr\FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$argsCount = count($functionCall->getArgs());
		$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		if ($argsCount === 0) {
			return TypeCombinator::remove($returnType, new ConstantBooleanType(false));
		}

		return $returnType;
	}

}
