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
use function count;
use function hash_algos;
use function in_array;

final class HashFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'hash';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		if (!isset($functionCall->getArgs()[0])) {
			return $defaultReturnType;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($argType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$values = TypeUtils::getConstantStrings($argType);
		if (count($values) !== 1) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}
		$string = $values[0];

		return in_array($string->getValue(), hash_algos(), true) ? new StringType() : new ConstantBooleanType(false);
	}

}
