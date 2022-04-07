<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use function array_key_exists;
use function array_merge;

/** @api */
class TypeUtils
{

	/**
	 * @return ArrayType[]
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
	 * @return ConstantType[]
	 */
	public static function getConstantTypes(Type $type): array
	{
		return self::map(ConstantType::class, $type, false);
	}

	/**
	 * @return ConstantType[]
	 */
	public static function getAnyConstantTypes(Type $type): array
	{
		return self::map(ConstantType::class, $type, false, false);
	}

	/**
	 * @return ArrayType[]
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
	 * @return string[]
	 */
	public static function getDirectClassNames(Type $type): array
	{
		if ($type instanceof TypeWithClassName) {
			return [$type->getClassName()];
		}

		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			$classNames = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof TypeWithClassName) {
					continue;
				}

				$classNames[] = $innerType->getClassName();
			}

			return $classNames;
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
	 * @return ConstantScalarType[]
	 */
	public static function getConstantScalars(Type $type): array
	{
		return self::map(ConstantScalarType::class, $type, false);
	}

	/**
	 * @internal
	 * @return ConstantArrayType[]
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

	public static function flattenConditionals(Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) {
			while ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
				$type = $type->getResult();
			}

			return $traverse($type);
		});
	}

	/**
	 * Replaces template types with standin types, and conditional types with their resolved types
	 *
	 * @param array<string, Type> $passedArgs
	 */
	public static function resolveTypes(Type $type, TemplateTypeMap $standins, array $passedArgs, bool $keepErrorTypes = false): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($standins, $passedArgs, $keepErrorTypes): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $standins->getType($type->getName());
				if ($newType === null) {
					return $traverse($type);
				}

				if ($newType instanceof ErrorType && !$keepErrorTypes) {
					return $traverse($type->getBound());
				}

				return $newType;
			}

			if ($type instanceof ConditionalTypeForParameter || $type instanceof ConditionalType) {
				$type = $traverse($type);

				if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $passedArgs)) {
					$type = $type->toConditional($passedArgs[$type->getParameterName()]);
				}

				if ($type instanceof ConditionalType) {
					return $type->resolve();
				}

				return $type;
			}

			return $traverse($type);
		});
	}

}
