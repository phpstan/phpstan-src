<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class GettimeofdayDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'gettimeofday';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayType = new ConstantArrayType([
			new ConstantStringType('sec'),
			new ConstantStringType('usec'),
			new ConstantStringType('minuteswest'),
			new ConstantStringType('dsttime'),
		], [
			new IntegerType(),
			new IntegerType(),
			new IntegerType(),
			new IntegerType(),
		]);
		$floatType = new FloatType();

		if (!isset($functionCall->getArgs()[0])) {
			return $arrayType;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$isTrueType = $argType->isTrue();
		$isFalseType = $argType->isFalse();
		$compareTypes = $isTrueType->compareTo($isFalseType);
		if ($compareTypes === $isTrueType) {
			return $floatType;
		}
		if ($compareTypes === $isFalseType) {
			return $arrayType;
		}

		if ($argType instanceof MixedType) {
			return new BenevolentUnionType([$arrayType, $floatType]);
		}

		return new UnionType([$arrayType, $floatType]);
	}

}
