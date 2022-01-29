<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function get_class;
use function in_array;
use function is_numeric;
use function sprintf;
use function strtolower;

class ParserNodeTypeToPHPStanType
{

	public static function resolveParameterDefaultType(?Expr $type): Type
	{
		if ($type instanceof ConstFetch) {
			$constName = (string) $type->name;
			$loweredConstName = strtolower($constName);
			if ($loweredConstName === 'true') {
				return new ConstantBooleanType(true);
			} elseif ($loweredConstName === 'false') {
				return new ConstantBooleanType(false);
			} elseif ($loweredConstName === 'null') {
				return new NullType();
			}

			return self::resolve(new Identifier($type->name->toString()), null);
		} elseif ($type instanceof ClassConstFetch) {
			if ($type->name instanceof Identifier) {
				$constantName = $type->name->name;
				if (!($type->class instanceof Name)) {
					throw new ShouldNotHappenException(sprintf('Unexpected type %s', get_class($type)));
				}

				$constantClass = (string) $type->class;
				$constantClassType = new ObjectType($constantClass);

				if (!$constantClassType->hasConstant($constantName)->yes()) {
					return new ErrorType();
				}

				return $constantClassType->getConstant($constantName)->getValueType();
			}

			throw new ShouldNotHappenException(sprintf('Unexpected type %s', get_class($type)));
		} elseif ($type instanceof String_ || $type instanceof LNumber || $type instanceof DNumber) {
			return ConstantTypeHelper::getTypeFromValue($type->value);
		} elseif ($type instanceof Array_) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($type->items as $item) {
				if ($item === null) {
					continue;
				}

				$keyType = null;
				if ($item->key !== null) {
					$keyType = self::resolveParameterDefaultType($item->key);
				}
				$arrayBuilder->setOffsetValueType(
					$keyType,
					self::resolveParameterDefaultType($item->value),
				);
			}

			return $arrayBuilder->getArray();
		} elseif ($type instanceof UnaryMinus) {
			$expr = $type->expr;

			if (!($expr instanceof LNumber || $expr instanceof DNumber)) {
				throw new ShouldNotHappenException(sprintf('Unexpected type %s', get_class($type)));
			}

			$type = self::resolveParameterDefaultType($expr);

			if ($type instanceof ConstantScalarType) {
				$value = $type->getValue();

				if (is_numeric($value)) {
					return ConstantTypeHelper::getTypeFromValue($value * -1);
				}
			}

			throw new ShouldNotHappenException(sprintf('Unexpected type %s', get_class($type)));
		} elseif ($type instanceof BitwiseOr || $type instanceof BitwiseAnd) {

			$left = self::resolveParameterDefaultType($type->left);
			$right = self::resolveParameterDefaultType($type->right);

			if ($left instanceof ConstantIntegerType && $right instanceof ConstantIntegerType) {
				if ($type instanceof BitwiseOr) {
					return new ConstantIntegerType($left->getValue() | $right->getValue());
				}
				return new ConstantIntegerType($left->getValue() & $right->getValue());
			}

			// unresolvable constant, e.g. unknown or class not found
			return new ErrorType();
		}

		if ($type) {
			throw new ShouldNotHappenException(sprintf('Unexpected type %s', get_class($type)));
		}
		throw new ShouldNotHappenException('Type cannot be null');
	}

	/**
	 * @param Node\Name|Node\Identifier|Node\ComplexType|null $type
	 */
	public static function resolve($type, ?ClassReflection $classReflection): Type
	{
		if ($type === null) {
			return new MixedType();
		} elseif ($type instanceof Name) {
			$typeClassName = (string) $type;
			$lowercasedClassName = strtolower($typeClassName);
			if ($classReflection !== null && in_array($lowercasedClassName, ['self', 'static'], true)) {
				if ($lowercasedClassName === 'static') {
					return new StaticType($classReflection);
				}
				$typeClassName = $classReflection->getName();
			} elseif (
				$lowercasedClassName === 'parent'
				&& $classReflection !== null
				&& $classReflection->getParentClass() !== null
			) {
				$typeClassName = $classReflection->getParentClass()->getName();
			}

			return new ObjectType($typeClassName);
		} elseif ($type instanceof NullableType) {
			return TypeCombinator::addNull(self::resolve($type->type, $classReflection));
		} elseif ($type instanceof Node\UnionType) {
			$types = [];
			foreach ($type->types as $unionTypeType) {
				$types[] = self::resolve($unionTypeType, $classReflection);
			}

			return TypeCombinator::union(...$types);
		} elseif ($type instanceof Node\IntersectionType) {
			$types = [];
			foreach ($type->types as $intersectionTypeType) {
				$innerType = self::resolve($intersectionTypeType, $classReflection);
				if (!$innerType instanceof ObjectType) {
					return new NeverType();
				}

				$types[] = $innerType;
			}

			return TypeCombinator::intersect(...$types);

		} elseif (!$type instanceof Identifier) {
			throw new ShouldNotHappenException(sprintf('Unexpected type %s', get_class($type)));
		}

		$type = $type->name;
		if ($type === 'string') {
			return new StringType();
		} elseif ($type === 'int') {
			return new IntegerType();
		} elseif ($type === 'bool') {
			return new BooleanType();
		} elseif ($type === 'float') {
			return new FloatType();
		} elseif ($type === 'callable') {
			return new CallableType();
		} elseif ($type === 'array') {
			return new ArrayType(new MixedType(), new MixedType());
		} elseif ($type === 'iterable') {
			return new IterableType(new MixedType(), new MixedType());
		} elseif ($type === 'void') {
			return new VoidType();
		} elseif ($type === 'object') {
			return new ObjectWithoutClassType();
		} elseif ($type === 'false') {
			return new ConstantBooleanType(false);
		} elseif ($type === 'null') {
			return new NullType();
		} elseif ($type === 'mixed') {
			return new MixedType(true);
		} elseif ($type === 'never') {
			return new NeverType(true);
		}

		return new MixedType();
	}

}
