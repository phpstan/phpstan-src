<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

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
			default:
				return new ObjectType($typeString);
		}
	}

	/** @api */
	public static function decideTypeFromReflection(
		?\ReflectionType $reflectionType,
		?Type $phpDocType = null,
		?string $selfClass = null,
		bool $isVariadic = false
	): Type
	{
		if ($reflectionType === null) {
			if ($isVariadic && $phpDocType instanceof ArrayType) {
				$phpDocType = $phpDocType->getItemType();
			}
			return $phpDocType ?? new MixedType();
		}

		if ($reflectionType instanceof ReflectionUnionType) {
			$type = TypeCombinator::union(...array_map(static function (ReflectionType $type) use ($selfClass): Type {
				return self::decideTypeFromReflection($type, null, $selfClass, false);
			}, $reflectionType->getTypes()));

			return self::decideType($type, $phpDocType);
		}

		if (!$reflectionType instanceof ReflectionNamedType) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Unexpected type: %s', get_class($reflectionType)));
		}

		$reflectionTypeString = $reflectionType->getName();
		if (\Nette\Utils\Strings::endsWith(strtolower($reflectionTypeString), '\\object')) {
			$reflectionTypeString = 'object';
		}
		if (\Nette\Utils\Strings::endsWith(strtolower($reflectionTypeString), '\\mixed')) {
			$reflectionTypeString = 'mixed';
		}
		if (\Nette\Utils\Strings::endsWith(strtolower($reflectionTypeString), '\\false')) {
			$reflectionTypeString = 'false';
		}
		if (\Nette\Utils\Strings::endsWith(strtolower($reflectionTypeString), '\\null')) {
			$reflectionTypeString = 'null';
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
		?Type $phpDocType = null
	): Type
	{
		if ($phpDocType !== null && !$phpDocType instanceof ErrorType) {
			if ($phpDocType instanceof NeverType && $phpDocType->isExplicit()) {
				return $phpDocType;
			}
			if ($type instanceof VoidType) {
				return new VoidType();
			}
			if (
				$type instanceof MixedType
				&& !$type->isExplicitMixed()
				&& $phpDocType instanceof VoidType
			) {
				return $phpDocType;
			}

			if (TypeCombinator::removeNull($type) instanceof IterableType) {
				if ($phpDocType instanceof UnionType) {
					$innerTypes = [];
					foreach ($phpDocType->getTypes() as $innerType) {
						if ($innerType instanceof ArrayType) {
							$innerTypes[] = new IterableType(
								$innerType->getKeyType(),
								$innerType->getItemType()
							);
						} else {
							$innerTypes[] = $innerType;
						}
					}
					$phpDocType = new UnionType($innerTypes);
				} elseif ($phpDocType instanceof ArrayType) {
					$phpDocType = new IterableType(
						$phpDocType->getKeyType(),
						$phpDocType->getItemType()
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
