<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;

final class ArrayRandFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_rand';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$argsCount = count($functionCall->getArgs());
		if ($argsCount < 1) {
			return null;
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);
		$isInteger = $firstArgType->getIterableKeyType()->isInteger();
		$isString = $firstArgType->getIterableKeyType()->isString();

		if ($isInteger->yes()) {
			$valueType = new IntegerType();
		} elseif ($isString->yes()) {
			$valueType = new StringType();
		} else {
			$valueType = new UnionType([new IntegerType(), new StringType()]);
		}

		if ($argsCount < 2) {
			return $valueType;
		}

		$secondArgType = $scope->getType($functionCall->getArgs()[1]->value);

		$one = new ConstantIntegerType(1);
		if ($one->isSuperTypeOf($secondArgType)->yes()) {
			return $valueType;
		}

		$bigger2 = IntegerRangeType::fromInterval(2, null);
		if ($bigger2->isSuperTypeOf($secondArgType)->yes()) {
			return new ArrayType(new IntegerType(), $valueType);
		}

		return TypeCombinator::union($valueType, new ArrayType(new IntegerType(), $valueType));
	}

}
