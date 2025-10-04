<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
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

	public static function getType(?PhpVersion $phpVersion = null): Type
	{
		$types = [
			new IntegerType(),
			new StringType(),
			new BooleanType(),
		];

		if ($phpVersion === null || !$phpVersion->deprecatesImplicitlyFloatConversionToInt()) {
			$types[] = new FloatType();
		}
		if ($phpVersion === null || !$phpVersion->deprecatesNullArrayOffset()) {
			$types[] = new NullType();
		}

		return new UnionType($types);
	}

	public static function narrowOffsetKeyType(Type $varType, Type $keyType): ?Type
	{
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

			return $narrowedKey;
		} elseif ($varIterableKeyType->isInteger()->yes() && $keyType->isString()->yes()) {
			return TypeCombinator::intersect($varIterableKeyType->toString(), $keyType);
		} elseif ($varType->isList()->yes()) {
			return TypeCombinator::intersect(
				$varIterableKeyType,
				$keyType,
			);
		}

		return new MixedType(
			subtractedType: new UnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new ObjectWithoutClassType(),
				new ResourceType(),
			]),
		);
	}

}
