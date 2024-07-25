<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

final class CurlInitReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'curl_init';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		Node\Expr\FuncCall $functionCall,
		Scope $scope,
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
