<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Widening of array shapes, what TypeCombinator::widen() does to the arrays of
 * a union and to the values merged into a general array: while the types hold
 * more than ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT constant array values,
 * the next step runs - the shapes fold first, keeping their keys, and then
 * degrade to oversized general arrays.
 *
 * @internal
 */
final class ArrayWidening
{

	/** @var list<WideningStep>|null */
	private static ?array $steps = null;

	/**
	 * @param list<Type> $types
	 * @return list<Type>
	 */
	public static function widen(array $types): array
	{
		foreach (self::$steps ??= [
			new SameKeysShapeWidening(),
			new SharedKeyShapeWidening(),
			new OversizedArrayWidening(),
		] as $step) {
			if (self::isWithinLimit($types)) {
				break;
			}
			$types = $step->apply($types);
		}

		return $types;
	}

	/** @param list<Type> $types */
	public static function isWithinLimit(array $types): bool
	{
		return TypeCombinator::countConstantArrayValueTypes($types) <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT;
	}

	/**
	 * The shape with its value types widened: merging shapes unions the values
	 * at each key, and those joins grow like any other.
	 */
	public static function widenValueTypes(ConstantArrayType $array): ConstantArrayType
	{
		$valueTypes = $array->getValueTypes();
		$changed = false;
		foreach ($valueTypes as $i => $valueType) {
			$widened = TypeCombinator::widen($valueType);
			if ($widened === $valueType) {
				continue;
			}
			$valueTypes[$i] = $widened;
			$changed = true;
		}
		if (!$changed) {
			return $array;
		}

		return new ConstantArrayType($array->getKeyTypes(), $valueTypes, $array->getNextAutoIndexes(), $array->getOptionalKeys(), $array->isList(), $array->getUnsealedTypes());
	}

	/** The type as a bare shape, or null when it is not one. */
	public static function asShape(Type $type): ?ConstantArrayType
	{
		// a bare ConstantArrayType is the only type whose getConstantArrays() is itself
		$shapes = $type->getConstantArrays();
		return $shapes === [$type] ? $shapes[0] : null;
	}

}
