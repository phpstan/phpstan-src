<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

final class ArrayChunkFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

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
		if (isset($functionCall->getArgs()[2])) {
			$preserveKeysType = $scope->getType($functionCall->getArgs()[2]->value);
			$preserveKeys = $preserveKeysType instanceof ConstantBooleanType ? $preserveKeysType->getValue() : null;
		} else {
			$preserveKeys = false;
		}

		if ($lengthType instanceof ConstantIntegerType && $lengthType->getValue() < 1) {
			return $this->phpVersion->throwsValueErrorForInternalFunctions() ? new NeverType() : new NullType();
		}

		if (!$arrayType->isArray()->yes()) {
			return null;
		}

		$constantArrays = $arrayType->getConstantArrays();
		if ($lengthType instanceof ConstantIntegerType && $lengthType->getValue() >= 1 && $preserveKeys !== null && count($constantArrays) > 0) {
			$results = [];
			foreach ($constantArrays as $constantArray) {
				$results[] = $constantArray->chunk($lengthType->getValue(), $preserveKeys);
			}

			return TypeCombinator::union(...$results);
		}

		$chunkType = self::getChunkType($arrayType, $preserveKeys);

		$resultType = AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), $chunkType));
		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$resultType = TypeCombinator::intersect($resultType, new NonEmptyArrayType());
		}

		return $resultType;
	}

	private static function getChunkType(Type $type, ?bool $preserveKeys): Type
	{
		if ($preserveKeys === null) {
			$chunkType = new ArrayType(TypeCombinator::union($type->getIterableKeyType(), new IntegerType()), $type->getIterableValueType());
		} elseif ($preserveKeys) {
			$chunkType = $type;
		} else {
			$chunkType = new ArrayType(new IntegerType(), $type->getIterableValueType());
			$chunkType = AccessoryArrayListType::intersectWith($chunkType);
		}

		return TypeCombinator::intersect($chunkType, new NonEmptyArrayType());
	}

}
