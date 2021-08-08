<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;

class StrlenFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strlen';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): \PHPStan\Type\Type
	{
		$args = $functionCall->args;
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($args[0]->value);

		if ($argType instanceof ConstantStringType) {
			return new ConstantIntegerType(strlen($argType->getValue()));
		}
		if ($argType instanceof BooleanType) {
			return IntegerRangeType::fromInterval(0, 1);
		}

		$isNonEmpty = $argType->isNonEmptyString();
		if ($isNonEmpty->yes() || $argType instanceof IntegerType) {
			return IntegerRangeType::fromInterval(1, null);
		}

		if ($isNonEmpty->no()) {
			return new ConstantIntegerType(0);
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

}
