<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

final class HashFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'hash';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if ($functionReflection->getName() === 'hash') {
			$defaultReturnType = new StringType();
		} else {
			$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		if (!isset($functionCall->args[0])) {
			return $defaultReturnType;
		}

		$argType = $scope->getType($functionCall->args[0]->value);
		if ($argType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$values = TypeUtils::getConstantStrings($argType);
		if (count($values) !== 1) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}
		$string = $values[0];

		return in_array($string->getValue(), hash_algos(), true) ? $defaultReturnType : new ConstantBooleanType(false);
	}

}
