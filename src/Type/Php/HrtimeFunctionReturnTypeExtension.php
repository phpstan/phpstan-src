<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

final class HrtimeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'hrtime';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayType = new ConstantArrayType([new ConstantIntegerType(0), new ConstantIntegerType(1)], [new IntegerType(), new IntegerType()], [2], [], TrinaryLogic::createYes());
		$numberType = TypeUtils::toBenevolentUnion(TypeCombinator::union(new IntegerType(), new FloatType()));

		if (count($functionCall->getArgs()) < 1) {
			return $arrayType;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$isTrueType = $argType->isTrue();
		$isFalseType = $argType->isFalse();
		$compareTypes = $isTrueType->compareTo($isFalseType);
		if ($compareTypes === $isTrueType) {
			return $numberType;
		}
		if ($compareTypes === $isFalseType) {
			return $arrayType;
		}

		return TypeCombinator::union($arrayType, $numberType);
	}

}
