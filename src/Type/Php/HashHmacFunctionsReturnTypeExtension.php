<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

final class HashHmacFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['hash_hmac', 'hash_hmac_file'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		$argType = $scope->getType($functionCall->args[0]->value);
		if ($argType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$values = TypeUtils::getConstantStrings($argType);
		if (count($values) !== 1) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}
		$string = $values[0];
		$availableAlgorithms = function_exists('hash_hmac_algos') ? hash_hmac_algos() : hash_algos();

		return in_array($string->getValue(), $availableAlgorithms, true) ? $defaultReturnType : new ConstantBooleanType(false);
	}

}
