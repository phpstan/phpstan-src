<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_values;
use function count;

/**
 * The last step: every non-empty shape degrades to an oversized general array
 * of its generalized keys and values, and when every array degraded, they
 * merge into one. Empty shapes stay as they are - with the oversized
 * accessory, they would be a contradictory `array{}&oversized-array`.
 *
 * @internal
 */
final class OversizedArrayWidening implements WideningStep
{

	public function apply(array $types): array
	{
		$results = [];
		$emptyArrays = [];
		$eachIsOversized = true;
		foreach ($types as $type) {
			if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
				$emptyArrays[] = $type;
				continue;
			}

			$isOversized = false;
			$result = TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$isOversized): Type {
				if (!$type instanceof ConstantArrayType) {
					return $traverse($type);
				}

				if ($type->isIterableAtLeastOnce()->no()) {
					return $type;
				}

				$isOversized = true;

				return self::degrade($type);
			});

			if (!$isOversized) {
				$eachIsOversized = false;
			}

			$results[] = $result;
		}

		if (!$eachIsOversized || $results === []) {
			return [...$emptyArrays, ...$results];
		}

		$eachIsList = true;
		$keyTypes = [];
		$valueTypes = [];
		foreach ($results as $result) {
			$keyTypes[] = $result->getIterableKeyType();
			$valueTypes[] = $result->getIterableValueType();
			if ($result->isList()->yes()) {
				continue;
			}
			$eachIsList = false;
		}

		$keyType = TypeCombinator::union(...$keyTypes);
		$valueType = TypeCombinator::widen(TypeCombinator::union(...$valueTypes));

		if ($valueType instanceof UnionType && count($valueType->getTypes()) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			$valueType = $valueType->generalize(GeneralizePrecision::lessSpecific());
		}

		return [...$emptyArrays, self::oversizedArray($keyType, $valueType, $eachIsList)];
	}

	private static function degrade(ConstantArrayType $type): Type
	{
		$isList = true;
		$valueTypes = [];
		$keyTypes = [];
		$nextAutoIndex = 0;
		$innerValueTypes = $type->getValueTypes();
		foreach ($type->getKeyTypes() as $i => $innerKeyType) {
			if (!$innerKeyType instanceof ConstantIntegerType) {
				$isList = false;
			} elseif ($innerKeyType->getValue() !== $nextAutoIndex) {
				$isList = false;
				$nextAutoIndex = $innerKeyType->getValue() + 1;
			} else {
				$nextAutoIndex++;
			}

			$generalizedKeyType = $innerKeyType->generalize(GeneralizePrecision::moreSpecific());
			$keyTypes[$generalizedKeyType->describe(VerbosityLevel::precise())] = $generalizedKeyType;

			// Inner traversal of the value position. Two subtleties, both
			// of which produced types that failed to be super-types of
			// their contributors:
			// - Empty constant arrays must be left alone; wrapping them
			//   builds a contradictory `array{}&oversized-array`.
			// - Fall through via `$innerTraverse`, not the outer
			//   traversal. The outer callback fully generalizes a
			//   sealed `ConstantArrayType` into `array<intKey, V>&...`,
			//   which is correct at the top level but wrong inside a
			//   value position: it would treat a sealed `array{a: 1}`
			//   reached via `array{}|array{a: 1}` differently from one
			//   reached directly, leaving the union with a mix of shapes
			//   it cannot unify cleanly.
			$generalizedValueType = TypeTraverser::map($innerValueTypes[$i], static function (Type $type, callable $innerTraverse): Type {
				if ($type instanceof ConstantArrayType && $type->isIterableAtLeastOnce()->no()) {
					return $type;
				}

				if ($type instanceof ArrayType || $type instanceof ConstantArrayType) {
					return new IntersectionType([$type, new OversizedArrayType()]);
				}

				if ($type instanceof ConstantScalarType) {
					return $type->generalize(GeneralizePrecision::moreSpecific());
				}

				return $innerTraverse($type);
			});
			$valueTypes[$generalizedValueType->describe(VerbosityLevel::precise())] = $generalizedValueType;
		}

		return self::oversizedArray(
			TypeCombinator::union(...array_values($keyTypes)),
			TypeCombinator::widen(TypeCombinator::union(...array_values($valueTypes))),
			$isList,
		);
	}

	private static function oversizedArray(Type $keyType, Type $valueType, bool $isList): Type
	{
		$accessories = [];
		if ($isList) {
			$accessories[] = new AccessoryArrayListType();
		}
		$accessories[] = new NonEmptyArrayType();
		$accessories[] = new OversizedArrayType();

		return TypeCombinator::intersect(new ArrayType($keyType, $valueType), ...$accessories);
	}

}
