<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function array_map;
use function count;
use function get_class;
use function sprintf;
use function str_ends_with;
use function strtolower;

class TypehintHelper
{

	private static function getTypeObjectFromTypehint(string $typeString, ?string $selfClass): Type
	{
		switch (strtolower($typeString)) {
			case 'int':
				return new IntegerType();
			case 'bool':
				return new BooleanType();
			case 'false':
				return new ConstantBooleanType(false);
			case 'true':
				return new ConstantBooleanType(true);
			case 'string':
				return new StringType();
			case 'float':
				return new FloatType();
			case 'array':
				return new ArrayType(new MixedType(), new MixedType());
			case 'iterable':
				return new IterableType(new MixedType(), new MixedType());
			case 'callable':
				return new CallableType();
			case 'void':
				return new VoidType();
			case 'object':
				return new ObjectWithoutClassType();
			case 'mixed':
				return new MixedType(true);
			case 'self':
				return $selfClass !== null ? new ObjectType($selfClass) : new ErrorType();
			case 'parent':
				$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
				if ($selfClass !== null && $reflectionProvider->hasClass($selfClass)) {
					$classReflection = $reflectionProvider->getClass($selfClass);
					if ($classReflection->getParentClass() !== null) {
						return new ObjectType($classReflection->getParentClass()->getName());
					}
				}
				return new NonexistentParentClassType();
			case 'static':
				$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
				if ($selfClass !== null && $reflectionProvider->hasClass($selfClass)) {
					return new StaticType($reflectionProvider->getClass($selfClass));
				}

				return new ErrorType();
			case 'null':
				return new NullType();
			case 'never':
				return new NeverType(true);
			default:
				return new ObjectType($typeString);
		}
	}

	/** @api */
	public static function decideTypeFromReflection(
		?ReflectionType $reflectionType,
		?Type $phpDocType = null,
		?string $selfClass = null,
		bool $isVariadic = false,
	): Type
	{
		if ($reflectionType === null) {
			if ($isVariadic && $phpDocType !== null && $phpDocType->isArray()->yes()) {
				$phpDocType = $phpDocType->getIterableValueType();
			}
			return $phpDocType ?? new MixedType();
		}

		if ($reflectionType instanceof ReflectionUnionType) {
			$type = TypeCombinator::union(...array_map(static fn (ReflectionType $type): Type => self::decideTypeFromReflection($type, null, $selfClass, false), $reflectionType->getTypes()));

			return self::decideType($type, $phpDocType);
		}

		if ($reflectionType instanceof ReflectionIntersectionType) {
			$types = [];
			foreach ($reflectionType->getTypes() as $innerReflectionType) {
				$innerType = self::decideTypeFromReflection($innerReflectionType, null, $selfClass, false);
				if (!$innerType instanceof ObjectType) {
					return new NeverType();
				}

				$types[] = $innerType;
			}

			return self::decideType(TypeCombinator::intersect(...$types), $phpDocType);
		}

		if (!$reflectionType instanceof ReflectionNamedType) {
			throw new ShouldNotHappenException(sprintf('Unexpected type: %s', get_class($reflectionType)));
		}

		$reflectionTypeString = $reflectionType->getName();
		if (str_ends_with(strtolower($reflectionTypeString), '\\object')) {
			$reflectionTypeString = 'object';
		}
		if (str_ends_with(strtolower($reflectionTypeString), '\\mixed')) {
			$reflectionTypeString = 'mixed';
		}
		if (str_ends_with(strtolower($reflectionTypeString), '\\true')) {
			$reflectionTypeString = 'true';
		}
		if (str_ends_with(strtolower($reflectionTypeString), '\\false')) {
			$reflectionTypeString = 'false';
		}
		if (str_ends_with(strtolower($reflectionTypeString), '\\null')) {
			$reflectionTypeString = 'null';
		}
		if (str_ends_with(strtolower($reflectionTypeString), '\\never')) {
			$reflectionTypeString = 'never';
		}

		$type = self::getTypeObjectFromTypehint($reflectionTypeString, $selfClass);
		if ($reflectionType->allowsNull()) {
			$type = TypeCombinator::addNull($type);
		} elseif ($phpDocType !== null) {
			$phpDocType = TypeCombinator::removeNull($phpDocType);
		}

		return self::decideType($type, $phpDocType);
	}

	public static function decideType(
		Type $type,
		?Type $phpDocType = null,
	): Type
	{
		if ($phpDocType !== null && !$phpDocType instanceof ErrorType) {
			if ($phpDocType instanceof NeverType && $phpDocType->isExplicit()) {
				return $phpDocType;
			}
			if (
				$type instanceof MixedType
				&& !$type->isExplicitMixed()
				&& $phpDocType->isVoid()->yes()
			) {
				return $phpDocType;
			}

			if (TypeCombinator::removeNull($type) instanceof IterableType) {
				if ($phpDocType instanceof UnionType) {
					$innerTypes = [];
					foreach ($phpDocType->getTypes() as $innerType) {
						if ($innerType->isArray()->yes()) {
							$innerTypes[] = new IterableType(
								$innerType->getIterableKeyType(),
								$innerType->getIterableValueType(),
							);
						} else {
							$innerTypes[] = $innerType;
						}
					}
					$phpDocType = new UnionType($innerTypes);
				} elseif ($phpDocType->isArray()->yes()) {
					$phpDocType = new IterableType(
						$phpDocType->getIterableKeyType(),
						$phpDocType->getIterableValueType(),
					);
				}
			}

			if (
				(!$phpDocType instanceof NeverType || ($type instanceof MixedType && !$type->isExplicitMixed()))
				&& $type->isSuperTypeOf(TemplateTypeHelper::resolveToBounds($phpDocType))->yes()
			) {
				$resultType = $phpDocType;
			} else {
				$resultType = $type;
			}

			if ($type instanceof UnionType) {
				$addToUnionTypes = [];
				foreach ($type->getTypes() as $innerType) {
					if (!$innerType->isSuperTypeOf($resultType)->no()) {
						continue;
					}

					$addToUnionTypes[] = $innerType;
				}

				if (count($addToUnionTypes) > 0) {
					$type = TypeCombinator::union($resultType, ...$addToUnionTypes);
				} else {
					$type = $resultType;
				}
			} elseif (TypeCombinator::containsNull($type)) {
				$type = TypeCombinator::addNull($resultType);
			} else {
				$type = $resultType;
			}
		}

		return $type;
	}

}
