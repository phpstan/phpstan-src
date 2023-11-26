<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class Base64DecodeDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'base64_decode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		if (!isset($functionCall->getArgs()[1])) {
			return new StringType();
		}

		$argType = $scope->getType($functionCall->getArgs()[1]->value);

		if ($argType instanceof MixedType) {
			return new BenevolentUnionType([new StringType(), new ConstantBooleanType(false)]);
		}

		$isTrueType = $argType->isTrue();
		$isFalseType = $argType->isFalse();
		$compareTypes = $isTrueType->compareTo($isFalseType);
		if ($compareTypes === $isTrueType) {
			return new UnionType([new StringType(), new ConstantBooleanType(false)]);
		}
		if ($compareTypes === $isFalseType) {
			return new StringType();
		}

		// second argument could be interpreted as true
		if (!$isTrueType->no()) {
			return new UnionType([new StringType(), new ConstantBooleanType(false)]);
		}

		return new StringType();
	}

}
