<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
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

		return TypeTraverser::map($arrayType, static function (Type $type, callable $traverse) use ($lengthType, $preserveKeys): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}

			if (
				$type instanceof ConstantArrayType
				&& $lengthType instanceof ConstantIntegerType
				&& $lengthType->getValue() >= 1
				&& $preserveKeys !== null
			) {
				return $type->chunk($lengthType->getValue(), $preserveKeys);
			}

			$chunkType = self::getChunkType($type, $preserveKeys);

			$accessoryTypes = [new AccessoryArrayListType()];
			if ($type->isIterableAtLeastOnce()->yes()) {
				$accessoryTypes[] = new NonEmptyArrayType();
			}

			return TypeCombinator::intersect(new ArrayType(new IntegerType(), $chunkType), ...$accessoryTypes);
		});
	}

	private static function getChunkType(Type $type, ?bool $preserveKeys): Type
	{
		$accessoryTypes = [new NonEmptyArrayType()];
		if ($preserveKeys === null) {
			$chunkType = new ArrayType(TypeCombinator::union($type->getIterableKeyType(), new IntegerType()), $type->getIterableValueType());
		} elseif ($preserveKeys) {
			$chunkType = $type;
		} else {
			$chunkType = new ArrayType(new IntegerType(), $type->getIterableValueType());
			$accessoryTypes[] = new AccessoryArrayListType();
		}

		return TypeCombinator::intersect($chunkType, ...$accessoryTypes);
	}

}
