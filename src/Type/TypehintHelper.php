<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeHelper;
use ReflectionType;
use function array_map;
use function count;
use function get_class;
use function is_string;
use function sprintf;

class TypehintHelper
{

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

		if ($reflectionType->isIdentifier()) {
			$typeNode = new Identifier($reflectionType->getName());
		} else {
			$typeNode = new FullyQualified($reflectionType->getName());
		}

		if (is_string($selfClass)) {
			$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
			if ($reflectionProvider->hasClass($selfClass)) {
				$selfClass = $reflectionProvider->getClass($selfClass);
			} else {
				$selfClass = null;
			}
		}
		$type = ParserNodeTypeToPHPStanType::resolve($typeNode, $selfClass);
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
								$innerType->getKeyType(),
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
