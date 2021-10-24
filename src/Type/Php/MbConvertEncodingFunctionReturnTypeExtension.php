<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class MbConvertEncodingFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'mb_convert_encoding';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		if (!isset($functionCall->getArgs()[0])) {
			return $defaultReturnType;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$isString = (new StringType())->isSuperTypeOf($argType);
		$isArray = (new ArrayType(new MixedType(), new MixedType()))->isSuperTypeOf($argType);
		$compare = $isString->compareTo($isArray);
		if ($compare === $isString) {
			return new StringType();
		} elseif ($compare === $isArray) {
			return new ArrayType(new IntegerType(), new StringType());
		}

		return $defaultReturnType;
	}

}
