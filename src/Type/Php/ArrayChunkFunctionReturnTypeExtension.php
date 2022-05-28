<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function count;

final class ArrayChunkFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_chunk';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		$lengthType = $scope->getType($functionCall->getArgs()[1]->value);
		$preserveKeysType = isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null;
		$preserveKeys = $preserveKeysType instanceof ConstantBooleanType ? $preserveKeysType->getValue() : false;

		if (!$arrayType->isArray()->yes() || !$lengthType instanceof ConstantIntegerType || $lengthType->getValue() < 1) {
			return null;
		}

		return TypeTraverser::map($arrayType, function (Type $type, callable $traverse) use ($lengthType, $preserveKeys): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}
			if ($type instanceof ConstantArrayType) {
				return $type->chunk($lengthType->getValue(), $preserveKeys);
			}
			return new ArrayType(new IntegerType(), $this->determineChunkTypeForGeneralArray($type, $preserveKeys));
		});
	}

	private function determineChunkTypeForGeneralArray(Type $type, bool $preserveKeys): Type
	{
		if ($preserveKeys) {
			return $type;
		}

		$chunkType = new ArrayType(new IntegerType(), $type->getIterableValueType());
		if ($type->isIterableAtLeastOnce()->yes()) {
			$chunkType = TypeCombinator::intersect($chunkType, new NonEmptyArrayType());
		}
		return $chunkType;
	}

}
