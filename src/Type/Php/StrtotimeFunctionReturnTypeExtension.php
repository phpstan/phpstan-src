<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function array_map;
use function array_unique;
use function count;
use function is_int;
use function strtotime;

class StrtotimeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strtotime';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		if (count($functionCall->getArgs()) === 0) {
			return $defaultReturnType;
		}
		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($argType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}
		$result = array_unique(array_map(static function (ConstantStringType $string): bool {
			return is_int(strtotime($string->getValue()));
		}, TypeUtils::getConstantStrings($argType)));

		if (count($result) !== 1) {
			return $defaultReturnType;
		}

		return $result[0] ? new IntegerType() : new ConstantBooleanType(false);
	}

}
