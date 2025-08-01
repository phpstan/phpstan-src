<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_merge;

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
		if ($type instanceof ConstantArrayType) {
			return $type->getAllArrays();
		}

		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				if ($innerType instanceof ConstantArrayType) {
					foreach ($innerType->getAllArrays() as $array) {
						$types[] = $array;
					}
					continue;
				}

				$types[] = $innerType;
			}

			return $types;
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
	 * @return AccessoryType[]
	 */
	public static function getAccessoryTypes(Type $type): array
	{
		return self::map(AccessoryType::class, $type, true, false);
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
		/** @var int $ignoreResolveUnresolvableTypesLevel */
		$ignoreResolveUnresolvableTypesLevel = 0;

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($resolveUnresolvableTypes, &$ignoreResolveUnresolvableTypesLevel): Type {
			while ($type instanceof LateResolvableType && (($resolveUnresolvableTypes && $ignoreResolveUnresolvableTypesLevel === 0) || $type->isResolvable())) {
				$type = $type->resolve();
			}

			if ($type instanceof CallableType || $type instanceof ClosureType) {
				$ignoreResolveUnresolvableTypesLevel++;
				$result = $traverse($type);
				$ignoreResolveUnresolvableTypesLevel--;

				return $result;
			}

			return $traverse($type);
		});
	}

}
