<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_merge;
use function array_unique;
use function array_values;

/** @api */
class TypeUtils
{

	/**
	 * @return ArrayType[]
	 *
	 * @deprecated Use PHPStan\Type\Type::getArrays() instead and handle optional ConstantArrayType keys if necessary.
	 */
	public static function getArrays(Type $type): array
	{
		if ($type instanceof ConstantArrayType) {
			return $type->getAllArrays();
		}

		if ($type instanceof ArrayType) {
			return [$type];
		}

		if ($type instanceof UnionType) {
			$matchingTypes = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof ArrayType) {
					return [];
				}
				foreach (self::getArrays($innerType) as $innerInnerType) {
					$matchingTypes[] = $innerInnerType;
				}
			}

			return $matchingTypes;
		}

		if ($type instanceof IntersectionType) {
			$matchingTypes = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof ArrayType) {
					continue;
				}
				foreach (self::getArrays($innerType) as $innerInnerType) {
					$matchingTypes[] = $innerInnerType;
				}
			}

			return $matchingTypes;
		}

		return [];
	}

	/**
	 * @return ConstantArrayType[]
	 *
	 * @deprecated Use PHPStan\Type\Type::getConstantArrays() instead and handle optional keys if necessary.
	 */
	public static function getConstantArrays(Type $type): array
	{
		if ($type instanceof ConstantArrayType) {
			return $type->getAllArrays();
		}

		if ($type instanceof UnionType) {
			$matchingTypes = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof ConstantArrayType) {
					return [];
				}
				foreach (self::getConstantArrays($innerType) as $innerInnerType) {
					$matchingTypes[] = $innerInnerType;
				}
			}

			return $matchingTypes;
		}

		return [];
	}

	/**
	 * @return ConstantStringType[]
	 *
	 * @deprecated Use PHPStan\Type\Type::getConstantStrings() instead
	 */
	public static function getConstantStrings(Type $type): array
	{
		return self::map(ConstantStringType::class, $type, false);
	}

	/**
	 * @return ConstantIntegerType[]
	 */
	public static function getConstantIntegers(Type $type): array
	{
		return self::map(ConstantIntegerType::class, $type, false);
	}

	/**
	 * @deprecated Use Type::isConstantValue() or Type::generalize()
	 * @return ConstantType[]
	 */
	public static function getConstantTypes(Type $type): array
	{
		return self::map(ConstantType::class, $type, false);
	}

	/**
	 * @deprecated Use Type::isConstantValue() or Type::generalize()
	 * @return ConstantType[]
	 */
	public static function getAnyConstantTypes(Type $type): array
	{
		return self::map(ConstantType::class, $type, false, false);
	}

	/**
	 * @return ArrayType[]
	 *
	 * @deprecated Use PHPStan\Type\Type::getArrays() instead.
	 */
	public static function getAnyArrays(Type $type): array
	{
		return self::map(ArrayType::class, $type, true, false);
	}

	/**
	 * @deprecated Use PHPStan\Type\Type::generalize() instead.
	 */
	public static function generalizeType(Type $type, GeneralizePrecision $precision): Type
	{
		return $type->generalize($precision);
	}

	/**
	 * @return list<string>
	 *
	 * @deprecated Use Type::getObjectClassNames() instead.
	 */
	public static function getDirectClassNames(Type $type): array
	{
		if ($type instanceof TypeWithClassName) {
			return [$type->getClassName()];
		}

		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			$classNames = [];
			foreach ($type->getTypes() as $innerType) {
				foreach (self::getDirectClassNames($innerType) as $n) {
					$classNames[] = $n;
				}
			}

			return array_values(array_unique($classNames));
		}

		return [];
	}

	/**
	 * @return IntegerRangeType[]
	 */
	public static function getIntegerRanges(Type $type): array
	{
		return self::map(IntegerRangeType::class, $type, false);
	}

	/**
	 * @deprecated Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues()
	 * @return ConstantScalarType[]
	 */
	public static function getConstantScalars(Type $type): array
	{
		return self::map(ConstantScalarType::class, $type, false);
	}

	/**
	 * @deprecated Use Type::getEnumCases()
	 * @return EnumCaseObjectType[]
	 */
	public static function getEnumCaseObjects(Type $type): array
	{
		return self::map(EnumCaseObjectType::class, $type, false);
	}

	/**
	 * @internal
	 * @return ConstantArrayType[]
	 *
	 * @deprecated Use PHPStan\Type\Type::getConstantArrays().
	 */
	public static function getOldConstantArrays(Type $type): array
	{
		return self::map(ConstantArrayType::class, $type, false);
	}

	/**
	 * @return mixed[]
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

	/** @deprecated Use PHPStan\Type\Type::isCallable() instead. */
	public static function containsCallable(Type $type): bool
	{
		if ($type->isCallable()->yes()) {
			return true;
		}

		if ($type instanceof UnionType) {
			foreach ($type->getTypes() as $innerType) {
				if ($innerType->isCallable()->yes()) {
					return true;
				}
			}
		}

		return false;
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
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($resolveUnresolvableTypes): Type {
			$type = $traverse($type);

			if ($type instanceof LateResolvableType && ($resolveUnresolvableTypes || $type->isResolvable())) {
				$type = $type->resolve();
			}

			return $type;
		});
	}

}
