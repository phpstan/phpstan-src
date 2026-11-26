<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use function count;

/**
 * Union and intersection of a type with a subtraction, `B~D`, and another type
 * that B contains: the tryUnion() and tryIntersect() of the subtractable types.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'subtractionAlgebra')]
final class SubtractionAlgebra
{

	/** @see Type::tryUnion() */
	public static function tryUnion(SubtractableType $type, Type $otherType): ?Type
	{
		if (!self::containsWithoutSubtraction($type, $otherType)) {
			return null;
		}

		return self::readmit($type, $otherType);
	}

	/** @see Type::tryIntersect() */
	public static function tryIntersect(SubtractableType $type, Type $otherType): ?Type
	{
		if (!self::containsWithoutSubtraction($type, $otherType)) {
			return null;
		}

		return self::subtractFrom($otherType, $type->getSubtractedType());
	}

	/** An explicit mixed does not contain an implicit one. */
	private static function containsWithoutSubtraction(SubtractableType $type, Type $otherType): bool
	{
		$withoutSubtractedType = $type->getTypeWithoutSubtractedType();
		if ($withoutSubtractedType instanceof MixedType && $otherType instanceof MixedType) {
			return $withoutSubtractedType->isSuperTypeOfMixed($otherType)->yes();
		}

		return $withoutSubtractedType->isSuperTypeOf($otherType)->yes();
	}

	/**
	 * `B~D | X` for an X that B contains: B again, minus what of D the X does
	 * not readmit.
	 */
	private static function readmit(SubtractableType $type, Type $readmittedType): Type
	{
		$subtractedType = $type->getSubtractedType();
		if ($subtractedType === null || $readmittedType instanceof NeverType) {
			return $type;
		}

		if ($readmittedType instanceof IntersectionType) {
			$subtractedTypes = [];
			$hasSubtractable = false;
			foreach ($readmittedType->getTypes() as $innerType) {
				if (!$innerType instanceof SubtractableType) {
					continue;
				}

				$hasSubtractable = true;
				$innerSubtractedType = $innerType->getSubtractedType();
				if ($innerSubtractedType === null) {
					continue;
				}

				$subtractedTypes[] = $innerSubtractedType;
			}

			if (!$hasSubtractable || count($subtractedTypes) === 0) {
				return $type->getTypeWithoutSubtractedType();
			}

			$stillSubtractedType = TypeCombinator::union(...$subtractedTypes);
		} else {
			$isAlreadySubtracted = $subtractedType->isSuperTypeOf($readmittedType);

			if ($isAlreadySubtracted->no()) {
				return $type;
			} elseif ($isAlreadySubtracted->yes()) {
				$stillSubtractedType = TypeCombinator::remove($subtractedType, $readmittedType);

				if (
					$stillSubtractedType instanceof NeverType
					|| !$stillSubtractedType->isSuperTypeOf($readmittedType)->no()
				) {
					$stillSubtractedType = null;
				}

				return $type->changeSubtractedType($stillSubtractedType);
			} elseif ($readmittedType instanceof SubtractableType) {
				$stillSubtractedType = $readmittedType->getSubtractedType();
				if ($stillSubtractedType === null) {
					return $type->getTypeWithoutSubtractedType();
				}
			} else {
				$subtractedPart = TypeCombinator::intersect($type->getTypeWithoutSubtractedType(), $subtractedType);
				if ($readmittedType->isSuperTypeOf($subtractedPart)->yes()) {
					return $type->getTypeWithoutSubtractedType();
				}
				$stillSubtractedType = new MixedType(subtractedType: $readmittedType);
			}
		}

		$stillSubtractedType = TypeCombinator::intersect($subtractedType, $stillSubtractedType);
		if ($stillSubtractedType instanceof NeverType) {
			$stillSubtractedType = null;
		}

		return $type->changeSubtractedType($stillSubtractedType);
	}

	/**
	 * `X & B~D` for an X that B contains: X without D.
	 */
	private static function subtractFrom(Type $type, ?Type $subtractedType): Type
	{
		if ($subtractedType === null) {
			return $type;
		}

		if ($subtractedType instanceof SubtractableType) {
			$withoutSubtracted = $subtractedType->getTypeWithoutSubtractedType();
			if ($withoutSubtracted->isSuperTypeOf($type)->yes()) {
				$subtractedSubtractedType = $subtractedType->getSubtractedType();
				if ($subtractedSubtractedType === null) {
					return new NeverType();
				}

				return TypeCombinator::intersect($type, $subtractedSubtractedType);
			}
		}

		if ($type instanceof SubtractableType) {
			$typeSubtractedType = $type->getSubtractedType();
			$subtractedType = $typeSubtractedType === null
				? $subtractedType
				: TypeCombinator::union($typeSubtractedType, $subtractedType);

			$subtractedType = TypeCombinator::intersect(
				$type->getTypeWithoutSubtractedType(),
				$subtractedType,
			);
			if ($subtractedType instanceof NeverType) {
				$subtractedType = null;
			}

			return $type->changeSubtractedType($subtractedType);
		}

		if ($subtractedType->isSuperTypeOf($type)->yes()) {
			return new NeverType();
		}

		return TypeCombinator::remove($type, $subtractedType);
	}

}
