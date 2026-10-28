<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\DependencyInjection\BleedingEdgeToggle;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function count;
use function strlen;

/**
 * Describes the accepted (given, returned, assigned) type of an "X expected,
 * Y given" error message. On bleeding edge, a description too long to read is
 * replaced with one where literal values are widened to their general types
 * (keeping what is known about them, like lowercase-string),
 * and big array shapes and lists of a single value type are turned into
 * general arrays, keeping the structure that usually tells where the type
 * differs from the accepting one.
 */
final class AcceptedTypeDescriptionHelper
{

	private const DESCRIPTION_LENGTH_LIMIT = 1000;

	private const DESCRIBED_ARRAY_SHAPE_KEYS_LIMIT = 8;

	public static function describe(Type $acceptingType, Type $acceptedType, VerbosityLevel $verbosityLevel): string
	{
		$description = $acceptedType->describe($verbosityLevel);
		if (strlen($description) <= self::DESCRIPTION_LENGTH_LIMIT || !BleedingEdgeToggle::isBleedingEdge()) {
			return $description;
		}

		$generalizedDescription = TypeTraverser::map($acceptedType, static function (Type $type, callable $traverse): Type {
			if ($type->isConstantScalarValue()->yes()) {
				return $type->generalize(GeneralizePrecision::moreSpecific());
			}

			$type = $traverse($type);
			if (!$type->isConstantArray()->yes()) {
				return $type;
			}

			$constantArrays = $type->getConstantArrays();
			if (count($constantArrays) !== 1 || count($constantArrays[0]->getKeyTypes()) === 0) {
				return $type;
			}

			if (
				count($constantArrays[0]->getKeyTypes()) <= self::DESCRIBED_ARRAY_SHAPE_KEYS_LIMIT
				&& !($type->isList()->yes() && self::hasSameValueTypes($constantArrays[0]->getValueTypes()))
			) {
				return $type;
			}

			$arrayType = new ArrayType($type->getIterableKeyType()->generalize(GeneralizePrecision::moreSpecific()), $type->getIterableValueType());
			if ($type->isList()->yes()) {
				$arrayType = TypeCombinator::intersect($arrayType, new AccessoryArrayListType());
			}
			if ($type->isIterableAtLeastOnce()->yes()) {
				$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
			}

			return $arrayType;
		})->describe($verbosityLevel);

		if (
			strlen($generalizedDescription) >= strlen($description)
			|| $generalizedDescription === $acceptingType->describe($verbosityLevel)
		) {
			return $description;
		}

		return $generalizedDescription;
	}

	/**
	 * @param array<Type> $valueTypes
	 */
	private static function hasSameValueTypes(array $valueTypes): bool
	{
		$firstValueType = null;
		foreach ($valueTypes as $valueType) {
			if ($firstValueType === null) {
				$firstValueType = $valueType;
				continue;
			}
			if (!$valueType->equals($firstValueType)) {
				return false;
			}
		}

		return true;
	}

}
