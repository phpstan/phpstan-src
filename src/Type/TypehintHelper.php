<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use ReflectionType;
use Traversable;
use function array_map;
use function count;
use function get_class;
use function sprintf;

final class TypehintHelper
{

	/** @api */
	public static function decideTypeFromReflection(
		?ReflectionType $reflectionType,
		?Type $phpDocType = null,
		ClassReflection|null $selfClass = null,
		bool $isVariadic = false,
	): Type
	{
		if ($reflectionType === null) {
			if ($isVariadic && ($phpDocType instanceof ArrayType || $phpDocType instanceof ConstantArrayType)) {
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
						if ($innerType instanceof ArrayType || $innerType instanceof ConstantArrayType) {
							$innerTypes[] = new IterableType(
								$innerType->getIterableKeyType(),
								$innerType->getItemType(),
							);
						} else {
							$innerTypes[] = $innerType;
						}
					}
					$phpDocType = new UnionType($innerTypes);
				} elseif ($phpDocType instanceof ArrayType || $phpDocType instanceof ConstantArrayType) {
					$phpDocType = new IterableType(
						$phpDocType->getKeyType(),
						$phpDocType->getItemType(),
					);
				}
			}

			$resolvedPhpDocTypeToBounds = TemplateTypeHelper::resolveToBounds($phpDocType);
			$isSuperType = $type->isSuperTypeOf($resolvedPhpDocTypeToBounds);
			if (
				!$isSuperType->yes()
				&& (new ObjectType(Traversable::class))->isSuperTypeOf($phpDocType)->yes()
				&& $type instanceof TypeWithClassName
			) {
				// if native type is Iterator and PHPDoc type is Traversable
				// Allow PHPDoc type to win
				$traversableAncestor = $type->getAncestorWithClassName(Traversable::class);
				if ($traversableAncestor !== null) {
					$isSuperType = $traversableAncestor->isSuperTypeOf($resolvedPhpDocTypeToBounds);
				}
			}

			if (
				(!$phpDocType instanceof NeverType || ($type instanceof MixedType && !$type->isExplicitMixed()))
				&& $isSuperType->yes()
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
