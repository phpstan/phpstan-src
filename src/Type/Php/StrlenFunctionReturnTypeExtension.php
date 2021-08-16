<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\TypeUtils;

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

		$constantStrings = TypeUtils::getConstantStrings($argType);
		$min = null;
		$max = null;
		foreach ($constantStrings as $constantString) {
			$len = strlen($constantString->getValue());

			if ($min === null || $len < $min) {
				$min = $len;
			}
			if ($max === null || $len > $max) {
				$max = $len;
			}
		}

		if ($min !== null || $max !== null) {
			return IntegerRangeType::fromInterval($min, $max);
		}

		if ($argType instanceof BooleanType) {
			return IntegerRangeType::fromInterval(0, 1);
		}

		$isNonEmpty = $argType->isNonEmptyString();
		$integer = new IntegerType();
		if ($isNonEmpty->yes() || $integer->isSuperTypeOf($argType)->yes()) {
			return IntegerRangeType::fromInterval(1, null);
		}

		if ($isNonEmpty->no()) {
			return new ConstantIntegerType(0);
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

}
