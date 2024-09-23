<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class AllowedArrayKeysTypes
{

	public static function getType(): Type
	{
		return new UnionType([
			new IntegerType(),
			new StringType(),
			new FloatType(),
			new BooleanType(),
			new NullType(),
		]);
	}

	public static function narrowOffsetKeyType(Type $varType): ?Type {
		if (!$varType->isArray()->yes() || $varType->isIterableAtLeastOnce()->no()) {
			return null;
		}

		$varIterableKeyType = $varType->getIterableKeyType();

		if ($varIterableKeyType->isConstantScalarValue()->yes()) {
			$narrowedKey = TypeCombinator::union(
				$varIterableKeyType,
				TypeCombinator::remove($varIterableKeyType->toString(), new ConstantStringType('')),
			);

			if (!$varType->hasOffsetValueType(new ConstantIntegerType(0))->no()) {
				$narrowedKey = TypeCombinator::union(
					$narrowedKey,
					new ConstantBooleanType(false),
				);
			}

			if (!$varType->hasOffsetValueType(new ConstantIntegerType(1))->no()) {
				$narrowedKey = TypeCombinator::union(
					$narrowedKey,
					new ConstantBooleanType(true),
				);
			}

			if (!$varType->hasOffsetValueType(new ConstantStringType(''))->no()) {
				$narrowedKey = TypeCombinator::addNull($narrowedKey);
			}

			if (!$varIterableKeyType->isNumericString()->no() || !$varIterableKeyType->isInteger()->no()) {
				$narrowedKey = TypeCombinator::union($narrowedKey, new FloatType());
			}
		} else {
			$narrowedKey = new MixedType(
				false,
				new UnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new ObjectWithoutClassType(),
					new ResourceType(),
				]),
			);
		}

		return $narrowedKey;
	}
}
