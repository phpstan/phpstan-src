<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\MagicConst\Line;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Node\Expr\MixedTypeExpr;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use function count;
use function dirname;
use function in_array;
use function is_int;
use function sprintf;
use function strtolower;

class InitializerExprTypeResolver
{

	private ?ReflectionProvider $reflectionProvider = null;

	public function __construct(
		private ConstantResolver $constantResolver,
		private ReflectionProviderProvider $reflectionProviderProvider,
	)
	{
	}

	public function getType(Expr $expr, InitializerExprContext $context): Type
	{
		if ($expr instanceof MixedTypeExpr) {
			return new MixedType();
		}
		if ($expr instanceof LNumber) {
			return new ConstantIntegerType($expr->value);
		}
		if ($expr instanceof DNumber) {
			return new ConstantFloatType($expr->value);
		}
		if ($expr instanceof String_) {
			return new ConstantStringType($expr->value);
		}
		if ($expr instanceof Expr\ConstFetch) {
			$constName = (string) $expr->name;
			$loweredConstName = strtolower($constName);
			if ($loweredConstName === 'true') {
				return new ConstantBooleanType(true);
			} elseif ($loweredConstName === 'false') {
				return new ConstantBooleanType(false);
			} elseif ($loweredConstName === 'null') {
				return new NullType();
			}

			$constant = $this->constantResolver->resolveConstant($expr->name, null);
			if ($constant !== null) {
				return $constant;
			}

			return new ErrorType();
		}
		if ($expr instanceof File) {
			$file = $context->getFile();
			return $file !== null ? new ConstantStringType($file) : new StringType();
		}
		if ($expr instanceof Dir) {
			$file = $context->getFile();
			return $file !== null ? new ConstantStringType(dirname($file)) : new StringType();
		}
		if ($expr instanceof Line) {
			return new ConstantIntegerType($expr->getLine());
		}
		if ($expr instanceof Expr\New_) {
			if ($expr->class instanceof Name) {
				return new ObjectType((string) $expr->class);
			}

			return new ObjectWithoutClassType();
		}
		if ($expr instanceof Expr\Array_) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			if (count($expr->items) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				$arrayBuilder->degradeToGeneralArray();
			}
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}
				$valueType = $this->getType($item->value, $context);
				if ($item->unpack) {
					if ($valueType instanceof ConstantArrayType) {
						$hasStringKey = false;
						foreach ($valueType->getKeyTypes() as $keyType) {
							if ($keyType instanceof ConstantStringType) {
								$hasStringKey = true;
								break;
							}
						}

						foreach ($valueType->getValueTypes() as $i => $innerValueType) {
							if ($hasStringKey) {
								$arrayBuilder->setOffsetValueType($valueType->getKeyTypes()[$i], $innerValueType);
							} else {
								$arrayBuilder->setOffsetValueType(null, $innerValueType);
							}
						}
					} else {
						$arrayBuilder->degradeToGeneralArray();

						if (! (new StringType())->isSuperTypeOf($valueType->getIterableKeyType())->no()) {
							$arrayBuilder->setOffsetValueType($valueType->getIterableKeyType(), $valueType->getIterableValueType());
						} else {
							$arrayBuilder->setOffsetValueType(new IntegerType(), $valueType->getIterableValueType(), !$valueType->isIterableAtLeastOnce()->yes() && !$valueType->getIterableValueType()->isIterableAtLeastOnce()->yes());
						}
					}
				} else {
					$arrayBuilder->setOffsetValueType(
						$item->key !== null ? $this->getType($item->key, $context) : null,
						$valueType,
					);
				}
			}
			return $arrayBuilder->getArray();
		}
		if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$var = $this->getType($expr->var, $context);
			$dim = $this->getType($expr->dim, $context);
			return $var->getOffsetValueType($dim);
		}
		if ($expr instanceof Expr\ClassConstFetch && $expr->name instanceof Identifier) {
			$constantName = $expr->name->name;
			$isObject = false;
			if ($expr->class instanceof Name) {
				$constantClass = (string) $expr->class;
				$constantClassType = new ObjectType($constantClass);
				$namesToResolve = [
					'self',
					'parent',
				];
				$classReflection = $context->getClass();
				if ($classReflection !== null) {
					if ($classReflection->isFinal()) {
						$namesToResolve[] = 'static';
					} elseif (strtolower($constantClass) === 'static') {
						if (strtolower($constantName) === 'class') {
							return new GenericClassStringType(new StaticType($classReflection));
						}

						$namesToResolve[] = 'static';
						$isObject = true;
					}
				}
				if (in_array(strtolower($constantClass), $namesToResolve, true)) {
					$resolvedName = $this->resolveName($expr->class, $context);
					if ($resolvedName === 'parent' && strtolower($constantName) === 'class') {
						return new ClassStringType();
					}
					$constantClassType = $this->resolveTypeByName($expr->class, $context);
				}

				if (strtolower($constantName) === 'class') {
					return new ConstantStringType($constantClassType->getClassName(), true);
				}
			} else {
				$constantClassType = $this->getType($expr->class, $context);
				$isObject = true;
			}

			if (strtolower($constantName) === 'class') {
				return TypeTraverser::map(
					$constantClassType,
					static function (Type $type, callable $traverse): Type {
						if ($type instanceof UnionType || $type instanceof IntersectionType) {
							return $traverse($type);
						}

						if ($type instanceof NullType) {
							return $type;
						}

						if ($type instanceof EnumCaseObjectType) {
							return new GenericClassStringType(new ObjectType($type->getClassName()));
						}

						if ($type instanceof TemplateType && !$type instanceof TypeWithClassName) {
							if ((new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()) {
								return new GenericClassStringType($type);
							}

							return new GenericClassStringType($type);
						} elseif ($type instanceof TypeWithClassName) {
							return new GenericClassStringType($type);
						} elseif ((new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()) {
							return new ClassStringType();
						}

						return new ErrorType();
					},
				);
			}

			$referencedClasses = TypeUtils::getDirectClassNames($constantClassType);
			$types = [];
			foreach ($referencedClasses as $referencedClass) {
				if (!$this->getReflectionProvider()->hasClass($referencedClass)) {
					continue;
				}

				$constantClassReflection = $this->getReflectionProvider()->getClass($referencedClass);
				if (!$constantClassReflection->hasConstant($constantName)) {
					continue;
				}

				if ($constantClassReflection->isEnum() && $constantClassReflection->hasEnumCase($constantName)) {
					$types[] = new EnumCaseObjectType($constantClassReflection->getName(), $constantName);
					continue;
				}

				$constantReflection = $constantClassReflection->getConstant($constantName);
				if (
					$constantReflection instanceof ClassConstantReflection
					&& $isObject
					&& !$constantClassReflection->isFinal()
					&& !$constantReflection->hasPhpDocType()
				) {
					return new MixedType();
				}

				if (
					$isObject
					&& (
						!$constantReflection instanceof ClassConstantReflection
						|| !$constantClassReflection->isFinal()
					)
				) {
					$constantType = $constantReflection->getValueType();
				} else {
					$constantType = $this->getType($constantReflection->getValueExpr(), $context);
				}

				$constantType = $this->constantResolver->resolveConstantType(
					sprintf('%s::%s', $constantClassReflection->getName(), $constantName),
					$constantType,
				);
				$types[] = $constantType;
			}

			if (count($types) > 0) {
				return TypeCombinator::union(...$types);
			}

			if (!$constantClassType->hasConstant($constantName)->yes()) {
				return new ErrorType();
			}

			return $constantClassType->getConstant($constantName)->getValueType();
		}
		if ($expr instanceof Expr\UnaryPlus) {
			return $this->getType($expr->expr, $context)->toNumber();
		}
		if ($expr instanceof Expr\UnaryMinus) {
			$type = $this->getType($expr->expr, $context)->toNumber();
			$scalarValues = TypeUtils::getConstantScalars($type);

			if (count($scalarValues) > 0) {
				$newTypes = [];
				foreach ($scalarValues as $scalarValue) {
					if ($scalarValue instanceof ConstantIntegerType) {
						/** @var int|float $newValue */
						$newValue = -$scalarValue->getValue();
						if (!is_int($newValue)) {
							return $type;
						}
						$newTypes[] = new ConstantIntegerType($newValue);
					} elseif ($scalarValue instanceof ConstantFloatType) {
						$newTypes[] = new ConstantFloatType(-$scalarValue->getValue());
					}
				}

				return TypeCombinator::union(...$newTypes);
			}

			if ($type instanceof IntegerRangeType) {
				return $this->getType(new Expr\BinaryOp\Mul($expr->expr, new LNumber(-1)), $context);
			}

			return $type;
		}
		if ($expr instanceof Expr\BinaryOp\Concat) {
			return $this->resolveConcatType($expr, $context);
		}
		if ($expr instanceof Expr\BinaryOp && !$expr instanceof Expr\BinaryOp\Coalesce) {
			$leftTypes = TypeUtils::getConstantScalars($this->getType($expr->left, $context));
			$rightTypes = TypeUtils::getConstantScalars($this->getType($expr->right, $context));

			$leftTypesCount = count($leftTypes);
			$rightTypesCount = count($rightTypes);
			if ($leftTypesCount > 0 && $rightTypesCount > 0) {
				$resultTypes = [];
				$generalize = $leftTypesCount * $rightTypesCount > MutatingScope::CALCULATE_SCALARS_LIMIT;
				foreach ($leftTypes as $leftType) {
					foreach ($rightTypes as $rightType) {
						$resultType = $this->calculateFromScalars($expr, $leftType, $rightType);
						if ($generalize) {
							$resultType = $resultType->generalize(GeneralizePrecision::lessSpecific());
						}
						$resultTypes[] = $resultType;
					}
				}
				return TypeCombinator::union(...$resultTypes);
			}
		}

		if ($expr instanceof Expr\FuncCall && $expr->name instanceof Name && $expr->name->toLowerString() === 'constant') {
			$firstArg = $expr->args[0] ?? null;
			if ($firstArg instanceof Arg && $firstArg->value instanceof String_) {
				$constant = $this->constantResolver->resolvePredefinedConstant($firstArg->value->value);
				if ($constant !== null) {
					return $constant;
				}
			}
		}

		if ($expr instanceof Expr\BooleanNot) {
			$exprBooleanType = $this->getType($expr->expr, $context)->toBoolean();

			if ($exprBooleanType instanceof ConstantBooleanType) {
				return new ConstantBooleanType(!$exprBooleanType->getValue());
			}

			return new BooleanType();
		}

		if ($expr instanceof Expr\BitwiseNot) {
			$exprType = $this->getType($expr->expr, $context);
			return TypeTraverser::map($exprType, static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof ConstantStringType) {
					return new ConstantStringType(~$type->getValue());
				}
				if ($type instanceof StringType) {
					return new StringType();
				}
				if ($type instanceof IntegerType || $type instanceof FloatType) {
					return new IntegerType(); //no const types here, result depends on PHP_INT_SIZE
				}
				return new ErrorType();
			});
		}

		// todo
		/*
		- [ ] Expr\Ternary
		- [ ] MagicConst\Class_
		- [ ] MagicConst\Namespace_
		- [ ] MagicConst\Method
		- [ ] MagicConst\Function_
		- [ ] MagicConst\Trait_
		 */

		return new MixedType();
	}

	private function resolveConcatType(Expr\BinaryOp\Concat $node, InitializerExprContext $context): Type
	{
		$leftStringType = $this->getType($node->left, $context)->toString();
		$rightStringType = $this->getType($node->right, $context)->toString();

		if (TypeCombinator::union($leftStringType, $rightStringType) instanceof ErrorType) {
			return new ErrorType();
		}

		if ($leftStringType instanceof ConstantStringType && $leftStringType->getValue() === '') {
			return $rightStringType;
		}

		if ($rightStringType instanceof ConstantStringType && $rightStringType->getValue() === '') {
			return $leftStringType;
		}

		if ($leftStringType instanceof ConstantStringType && $rightStringType instanceof ConstantStringType) {
			return $leftStringType->append($rightStringType);
		}

		// we limit the number of union-types for performance reasons
		if ($leftStringType instanceof UnionType && count($leftStringType->getTypes()) <= 16 && $rightStringType instanceof ConstantStringType) {
			$constantStrings = TypeUtils::getConstantStrings($leftStringType);
			if (count($constantStrings) > 0) {
				$strings = [];
				foreach ($constantStrings as $constantString) {
					if ($constantString->getValue() === '') {
						$strings[] = $rightStringType;

						continue;
					}
					$strings[] = $constantString->append($rightStringType);
				}
				return TypeCombinator::union(...$strings);
			}
		}
		if ($rightStringType instanceof UnionType && count($rightStringType->getTypes()) <= 16 && $leftStringType instanceof ConstantStringType) {
			$constantStrings = TypeUtils::getConstantStrings($rightStringType);
			if (count($constantStrings) > 0) {
				$strings = [];
				foreach ($constantStrings as $constantString) {
					if ($constantString->getValue() === '') {
						$strings[] = $leftStringType;

						continue;
					}
					$strings[] = $leftStringType->append($constantString);
				}
				return TypeCombinator::union(...$strings);
			}
		}

		$accessoryTypes = [];
		if ($leftStringType->isNonEmptyString()->or($rightStringType->isNonEmptyString())->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if ($leftStringType->isLiteralString()->and($rightStringType->isLiteralString())->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

	private function calculateFromScalars(Expr $node, ConstantScalarType $leftType, ConstantScalarType $rightType): Type
	{
		if ($leftType instanceof StringType && $rightType instanceof StringType) {
			/** @var string $leftValue */
			$leftValue = $leftType->getValue();
			/** @var string $rightValue */
			$rightValue = $rightType->getValue();

			if ($node instanceof Expr\BinaryOp\BitwiseAnd || $node instanceof Expr\AssignOp\BitwiseAnd) {
				return $this->getTypeFromValue($leftValue & $rightValue);
			}

			if ($node instanceof Expr\BinaryOp\BitwiseOr || $node instanceof Expr\AssignOp\BitwiseOr) {
				return $this->getTypeFromValue($leftValue | $rightValue);
			}

			if ($node instanceof Expr\BinaryOp\BitwiseXor || $node instanceof Expr\AssignOp\BitwiseXor) {
				return $this->getTypeFromValue($leftValue ^ $rightValue);
			}
		}

		$leftValue = $leftType->getValue();
		$rightValue = $rightType->getValue();

		if ($node instanceof Expr\BinaryOp\Spaceship) {
			return $this->getTypeFromValue($leftValue <=> $rightValue);
		}

		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();
		if (TypeCombinator::union($leftNumberType, $rightNumberType) instanceof ErrorType) {
			return new ErrorType();
		}

		if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
			throw new ShouldNotHappenException();
		}

		/** @var float|int $leftNumberValue */
		$leftNumberValue = $leftNumberType->getValue();

		/** @var float|int $rightNumberValue */
		$rightNumberValue = $rightNumberType->getValue();

		if ($node instanceof Expr\BinaryOp\Plus || $node instanceof Expr\AssignOp\Plus) {
			return $this->getTypeFromValue($leftNumberValue + $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\Minus || $node instanceof Expr\AssignOp\Minus) {
			return $this->getTypeFromValue($leftNumberValue - $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\Mul || $node instanceof Expr\AssignOp\Mul) {
			return $this->getTypeFromValue($leftNumberValue * $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\Pow || $node instanceof Expr\AssignOp\Pow) {
			return $this->getTypeFromValue($leftNumberValue ** $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\Div || $node instanceof Expr\AssignOp\Div) {
			if ($rightNumberValue === 0 || $rightNumberValue === 0.0) {
				return new ErrorType();
			}

			return $this->getTypeFromValue($leftNumberValue / $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\Mod || $node instanceof Expr\AssignOp\Mod) {
			$rightNumberValue = (int) $rightNumberValue;
			if ($rightNumberValue === 0) {
				return new ErrorType();
			}

			return $this->getTypeFromValue(((int) $leftNumberValue) % $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\ShiftLeft || $node instanceof Expr\AssignOp\ShiftLeft) {
			return $this->getTypeFromValue($leftNumberValue << $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\ShiftRight || $node instanceof Expr\AssignOp\ShiftRight) {
			return $this->getTypeFromValue($leftNumberValue >> $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\BitwiseAnd || $node instanceof Expr\AssignOp\BitwiseAnd) {
			return $this->getTypeFromValue($leftNumberValue & $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\BitwiseOr || $node instanceof Expr\AssignOp\BitwiseOr) {
			return $this->getTypeFromValue($leftNumberValue | $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\BitwiseXor || $node instanceof Expr\AssignOp\BitwiseXor) {
			return $this->getTypeFromValue($leftNumberValue ^ $rightNumberValue);
		}

		return new MixedType();
	}

	public function resolveName(Name $name, InitializerExprContext $context): string
	{
		$originalClass = (string) $name;
		$classReflection = $context->getClass();
		if ($classReflection !== null) {
			if (in_array(strtolower($originalClass), [
				'self',
				'static',
			], true)) {
				return $classReflection->getName();
			} elseif ($originalClass === 'parent') {
				if ($classReflection->getParentClass() !== null) {
					return $classReflection->getParentClass()->getName();
				}
			}
		}

		return $originalClass;
	}

	/** @api */
	public function resolveTypeByName(Name $name, InitializerExprContext $context): TypeWithClassName
	{
		$classReflection = $context->getClass();

		if ($name->toLowerString() === 'static' && $classReflection !== null) {
			return new StaticType($classReflection);
		}

		$originalClass = $this->resolveName($name, $context);
		if ($classReflection !== null) {
			$thisType = new ThisType($classReflection);
			$ancestor = $thisType->getAncestorWithClassName($originalClass);
			if ($ancestor !== null) {
				return $ancestor;
			}
		}

		return new ObjectType($originalClass);
	}

	/**
	 * @param mixed $value
	 */
	private function getTypeFromValue($value): Type
	{
		return ConstantTypeHelper::getTypeFromValue($value);
	}

	private function getReflectionProvider(): ReflectionProvider
	{
		return $this->reflectionProvider ??= $this->reflectionProviderProvider->getReflectionProvider();
	}

}
