<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Internal\CombinationsHelper;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Traverser\LateResolvableTraverser;
use function array_merge;
use function count;
use function max;
use const PHP_INT_MAX;

/**
 * @api
 */
final class TypeUtils
{

	/**
	 * @return list<ConstantIntegerType>
	 */
	public static function getConstantIntegers(Type $type): array
	{
		return self::map(ConstantIntegerType::class, $type, false);
	}

	/**
	 * @return list<IntegerRangeType>
	 */
	public static function getIntegerRanges(Type $type): array
	{
		return self::map(IntegerRangeType::class, $type, false);
	}

	/**
	 * @return list<mixed>
	 */
	private static function map(
		string $typeClass,
		Type $type,
		bool $inspectIntersections,
		bool $stopOnUnmatched = true,
	): array
	{
		if ($type instanceof $typeClass) {
			return [$type];
		}

		if ($type instanceof UnionType) {
			$matchingTypes = [];
			foreach ($type->getTypes() as $innerType) {
				$matchingInner = self::map($typeClass, $innerType, $inspectIntersections, $stopOnUnmatched);

				if ($matchingInner === []) {
					if ($stopOnUnmatched) {
						return [];
					}

					continue;
				}

				foreach ($matchingInner as $innerMapped) {
					$matchingTypes[] = $innerMapped;
				}
			}

			return $matchingTypes;
		}

		if ($inspectIntersections && $type instanceof IntersectionType) {
			$matchingTypes = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof $typeClass) {
					if ($stopOnUnmatched) {
						return [];
					}

					continue;
				}

				$matchingTypes[] = $innerType;
			}

			return $matchingTypes;
		}

		return [];
	}

	public static function toBenevolentUnion(Type $type): Type
	{
		if ($type instanceof BenevolentUnionType) {
			return $type;
		}

		if ($type instanceof UnionType) {
			return new BenevolentUnionType($type->getTypes());
		}

		return $type;
	}

	/**
	 * @return ($type is UnionType ? UnionType : Type)
	 */
	public static function toStrictUnion(Type $type): Type
	{
		if ($type instanceof TemplateBenevolentUnionType) {
			return new TemplateUnionType(
				$type->getScope(),
				$type->getStrategy(),
				$type->getVariance(),
				$type->getName(),
				static::toStrictUnion($type->getBound()),
				$type->getDefault(),
			);
		}

		if ($type instanceof BenevolentUnionType) {
			return new UnionType($type->getTypes());
		}

		return $type;
	}

	/**
	 * @return Type[]
	 */
	public static function flattenTypes(Type $type): array
	{
		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				$flattenTypes = self::flattenTypes($innerType);
				foreach ($flattenTypes as $flattenType) {
					$types[] = $flattenType;
				}
			}

			return $types;
		}

		$constantArrays = $type->getConstantArrays();
		if ($constantArrays !== []) {
			// Estimate the total number of power-set variants before expanding.
			// Each ConstantArrayType with N optional keys produces 2^N variants
			// from getAllArrays(). The cartesian product across multiple constant
			// arrays multiplies these counts. Bail out to avoid O(2^N) allocation
			// when the total would be large.
			$estimatedCount = 1;
			$bail = false;
			foreach ($constantArrays as $constantArray) {
				$optionalCount = count($constantArray->getOptionalKeys());
				$arrayCount = $optionalCount <= 20 ? (1 << $optionalCount) : PHP_INT_MAX;
				if ($arrayCount > 16384 || $estimatedCount > 16384 / max($arrayCount, 1)) {
					$bail = true;
					break;
				}
				$estimatedCount *= $arrayCount;
			}

			if ($bail) {
				return [$type];
			}

			$newTypes = [];
			foreach ($constantArrays as $constantArray) {
				$newTypes[] = $constantArray->getAllArrays();
			}

			$result = [];
			foreach (CombinationsHelper::combinations($newTypes) as $combination) {
				$intersected = $combination[0];
				for ($i = 1, $count = count($combination); $i < $count; $i++) {
					$intersected = TypeCombinator::intersect($intersected, $combination[$i]);
				}
				if ($intersected instanceof NeverType) {
					continue;
				}

				$result[] = $intersected;
			}

			return $result;
		}

		return [$type];
	}

	public static function findThisType(Type $type): ?ThisType
	{
		if ($type instanceof ThisType) {
			return $type;
		}

		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			foreach ($type->getTypes() as $innerType) {
				$thisType = self::findThisType($innerType);
				if ($thisType !== null) {
					return $thisType;
				}
			}
		}

		return null;
	}

	public static function findCallableType(Type $type): ?Type
	{
		if ($type->isCallable()->yes()) {
			return $type;
		}

		if ($type instanceof UnionType) {
			foreach ($type->getTypes() as $innerType) {
				$callableType = self::findCallableType($innerType);
				if ($callableType !== null) {
					return $callableType;
				}
			}
		}

		return null;
	}

	/**
	 * @return HasPropertyType[]
	 */
	public static function getHasPropertyTypes(Type $type): array
	{
		if ($type instanceof HasPropertyType) {
			return [$type];
		}

		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			$hasPropertyTypes = [[]];
			foreach ($type->getTypes() as $innerType) {
				$hasPropertyTypes[] = self::getHasPropertyTypes($innerType);
			}

			return array_merge(...$hasPropertyTypes);
		}

		return [];
	}

	/**
	 * @return list<AccessoryType>
	 */
	public static function getAccessoryTypes(Type $type): array
	{
		return self::map(AccessoryType::class, $type, inspectIntersections: true, stopOnUnmatched: false);
	}

	public static function containsTemplateType(Type $type): bool
	{
		$containsTemplateType = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$containsTemplateType): Type {
			if ($type instanceof TemplateType) {
				$containsTemplateType = true;
			}

			return $containsTemplateType ? $type : $traverse($type);
		});

		return $containsTemplateType;
	}

	public static function resolveLateResolvableTypes(Type $type, bool $resolveUnresolvableTypes = true): Type
	{
		if (!$type->hasTemplateOrLateResolvableType()) {
			return $type;
		}

		return TypeTraverser::map($type, new LateResolvableTraverser($resolveUnresolvableTypes));
	}

}
