<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
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
use function is_string;
use function sprintf;
use function str_ends_with;
use function strtolower;

final class TypehintHelper
{

	private static function getTypeObjectFromTypehint(string $typeString, ClassReflection|string|null $selfClass): Type
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
				if ($selfClass instanceof ClassReflection) {
					$selfClass = $selfClass->getName();
				}
				return $selfClass !== null ? new ObjectType($selfClass) : new ErrorType();
			case 'parent':
				$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
				if (is_string($selfClass)) {
					if ($reflectionProvider->hasClass($selfClass)) {
						$selfClass = $reflectionProvider->getClass($selfClass);
					} else {
						$selfClass = null;
					}
				}
				if ($selfClass !== null) {
					if ($selfClass->getParentClass() !== null) {
						return new ObjectType($selfClass->getParentClass()->getName());
					}
				}
				return new NonexistentParentClassType();
			case 'static':
				$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
				if (is_string($selfClass)) {
					if ($reflectionProvider->hasClass($selfClass)) {
						 $selfClass = $reflectionProvider->getClass($selfClass);
					} else {
						$selfClass = null;
					}
				}
				if ($selfClass !== null) {
					return new StaticType($selfClass);
				}

				return new ErrorType();
			case 'null':
				return new NullType();
			case 'never':
				return new NonAcceptingNeverType();
			default:
				return new ObjectType($typeString);
		}
	}

	/** @api */
	public static function decideTypeFromReflection(
		?ReflectionType $reflectionType,
		?Type $phpDocType = null,
		ClassReflection|string|null $selfClass = null,
		bool $isVariadic = false,
	): Type
	{
		if ($reflectionType === null) {
			if ($isVariadic && $phpDocType instanceof ArrayType) {
				$phpDocType = $phpDocType->getItemType();
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
				if (!$innerType->isObject()->yes()) {
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
		$loweredReflectionTypeString = strtolower($reflectionTypeString);
		if (str_ends_with($loweredReflectionTypeString, '\\object')) {
			$reflectionTypeString = 'object';
		} elseif (str_ends_with($loweredReflectionTypeString, '\\mixed')) {
			$reflectionTypeString = 'mixed';
		} elseif (str_ends_with($loweredReflectionTypeString, '\\true')) {
			$reflectionTypeString = 'true';
		} elseif (str_ends_with($loweredReflectionTypeString, '\\false')) {
			$reflectionTypeString = 'false';
		} elseif (str_ends_with($loweredReflectionTypeString, '\\null')) {
			$reflectionTypeString = 'null';
		} elseif (str_ends_with($loweredReflectionTypeString, '\\never')) {
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
		if ($type instanceof BenevolentUnionType) {
			return $type;
		}

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
						if ($innerType instanceof ArrayType) {
							$innerTypes[] = new IterableType(
								$innerType->getIterableKeyType(),
								$innerType->getItemType(),
							);
						} else {
							$innerTypes[] = $innerType;
						}
					}
					$phpDocType = new UnionType($innerTypes);
				} elseif ($phpDocType instanceof ArrayType) {
					$phpDocType = new IterableType(
						$phpDocType->getKeyType(),
						$phpDocType->getItemType(),
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
