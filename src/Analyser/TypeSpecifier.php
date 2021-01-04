<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use function array_reverse;

class TypeSpecifier
{

	private \PhpParser\PrettyPrinter\Standard $printer;

	private ReflectionProvider $reflectionProvider;

	/** @var \PHPStan\Type\FunctionTypeSpecifyingExtension[] */
	private array $functionTypeSpecifyingExtensions;

	/** @var \PHPStan\Type\MethodTypeSpecifyingExtension[] */
	private array $methodTypeSpecifyingExtensions;

	/** @var \PHPStan\Type\StaticMethodTypeSpecifyingExtension[] */
	private array $staticMethodTypeSpecifyingExtensions;

	/** @var \PHPStan\Type\MethodTypeSpecifyingExtension[][]|null */
	private ?array $methodTypeSpecifyingExtensionsByClass = null;

	/** @var \PHPStan\Type\StaticMethodTypeSpecifyingExtension[][]|null */
	private ?array $staticMethodTypeSpecifyingExtensionsByClass = null;

	/**
	 * @param \PhpParser\PrettyPrinter\Standard $printer
	 * @param ReflectionProvider $reflectionProvider
	 * @param \PHPStan\Type\FunctionTypeSpecifyingExtension[] $functionTypeSpecifyingExtensions
	 * @param \PHPStan\Type\MethodTypeSpecifyingExtension[] $methodTypeSpecifyingExtensions
	 * @param \PHPStan\Type\StaticMethodTypeSpecifyingExtension[] $staticMethodTypeSpecifyingExtensions
	 */
	public function __construct(
		\PhpParser\PrettyPrinter\Standard $printer,
		ReflectionProvider $reflectionProvider,
		array $functionTypeSpecifyingExtensions,
		array $methodTypeSpecifyingExtensions,
		array $staticMethodTypeSpecifyingExtensions
	)
	{
		$this->printer = $printer;
		$this->reflectionProvider = $reflectionProvider;

		foreach (array_merge($functionTypeSpecifyingExtensions, $methodTypeSpecifyingExtensions, $staticMethodTypeSpecifyingExtensions) as $extension) {
			if (!($extension instanceof TypeSpecifierAwareExtension)) {
				continue;
			}

			$extension->setTypeSpecifier($this);
		}

		$this->functionTypeSpecifyingExtensions = $functionTypeSpecifyingExtensions;
		$this->methodTypeSpecifyingExtensions = $methodTypeSpecifyingExtensions;
		$this->staticMethodTypeSpecifyingExtensions = $staticMethodTypeSpecifyingExtensions;
	}

	public function specifyTypesInCondition(
		Scope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
		bool $defaultHandleFunctions = false
	): SpecifiedTypes
	{
		if ($expr instanceof Instanceof_) {
			$exprNode = $expr->expr;
			if ($exprNode instanceof Expr\Assign) {
				$exprNode = $exprNode->var;
			}
			if ($expr->class instanceof Name) {
				$className = (string) $expr->class;
				$lowercasedClassName = strtolower($className);
				if ($lowercasedClassName === 'self' && $scope->isInClass()) {
					$type = new ObjectType($scope->getClassReflection()->getName());
				} elseif ($lowercasedClassName === 'static' && $scope->isInClass()) {
					$type = new StaticType($scope->getClassReflection()->getName());
				} elseif ($lowercasedClassName === 'parent') {
					if (
						$scope->isInClass()
						&& $scope->getClassReflection()->getParentClass() !== false
					) {
						$type = new ObjectType($scope->getClassReflection()->getParentClass()->getName());
					} else {
						$type = new NonexistentParentClassType();
					}
				} else {
					$type = new ObjectType($className);
				}
				return $this->create($exprNode, $type, $context);
			}

			$classType = $scope->getType($expr->class);
			$type = TypeTraverser::map($classType, static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof TypeWithClassName) {
					return $type;
				}
				if ($type instanceof GenericClassStringType) {
					return $type->getGenericType();
				}
				if ($type instanceof ConstantStringType) {
					return new ObjectType($type->getValue());
				}
				return new MixedType();
			});

			if (!$type->isSuperTypeOf(new MixedType())->yes()) {
				if ($context->true()) {
					$type = TypeCombinator::intersect(
						$type,
						new ObjectWithoutClassType()
					);
				}
				return $this->create($exprNode, $type, $context);
			}
			if ($context->true()) {
				return $this->create($exprNode, new ObjectWithoutClassType(), $context);
			}
		} elseif ($expr instanceof Node\Expr\BinaryOp\Identical) {
			$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
			if ($expressions !== null) {
				/** @var Expr $exprNode */
				$exprNode = $expressions[0];
				if ($exprNode instanceof Expr\Assign) {
					$exprNode = $exprNode->var;
				}
				/** @var \PHPStan\Type\ConstantScalarType $constantType */
				$constantType = $expressions[1];
				if ($constantType->getValue() === false) {
					$types = $this->create($exprNode, $constantType, $context);
					return $types->unionWith($this->specifyTypesInCondition(
						$scope,
						$exprNode,
						$context->true() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createFalse()->negate()
					));
				}

				if ($constantType->getValue() === true) {
					$types = $this->create($exprNode, $constantType, $context);
					return $types->unionWith($this->specifyTypesInCondition(
						$scope,
						$exprNode,
						$context->true() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createTrue()->negate()
					));
				}

				if ($constantType->getValue() === null) {
					return $this->create($exprNode, $constantType, $context);
				}

				if (
					!$context->null()
					&& $exprNode instanceof FuncCall
					&& count($exprNode->args) === 1
					&& $exprNode->name instanceof Name
					&& strtolower((string) $exprNode->name) === 'count'
					&& $constantType instanceof ConstantIntegerType
				) {
					if ($context->truthy() || $constantType->getValue() === 0) {
						$newContext = $context;
						if ($constantType->getValue() === 0) {
							$newContext = $newContext->negate();
						}
						$argType = $scope->getType($exprNode->args[0]->value);
						if ((new ArrayType(new MixedType(), new MixedType()))->isSuperTypeOf($argType)->yes()) {
							return $this->create($exprNode->args[0]->value, new NonEmptyArrayType(), $newContext);
						}
					}
				}
			}

			if ($context->true()) {
				$type = TypeCombinator::intersect($scope->getType($expr->right), $scope->getType($expr->left));
				$leftTypes = $this->create($expr->left, $type, $context);
				$rightTypes = $this->create($expr->right, $type, $context);
				return $leftTypes->unionWith($rightTypes);

			} elseif ($context->false()) {
				$identicalType = $scope->getType($expr);
				if ($identicalType instanceof ConstantBooleanType) {
					$never = new NeverType();
					$contextForTypes = $identicalType->getValue() ? $context->negate() : $context;
					$leftTypes = $this->create($expr->left, $never, $contextForTypes);
					$rightTypes = $this->create($expr->right, $never, $contextForTypes);
					return $leftTypes->unionWith($rightTypes);
				}

				$exprLeftType = $scope->getType($expr->left);
				$exprRightType = $scope->getType($expr->right);

				$types = null;

				if (
					$exprLeftType instanceof ConstantType
					&& !$expr->right instanceof Node\Scalar
				) {
					$types = $this->create(
						$expr->right,
						$exprLeftType,
						$context
					);
				}
				if (
					$exprRightType instanceof ConstantType
					&& !$expr->left instanceof Node\Scalar
				) {
					$leftType = $this->create(
						$expr->left,
						$exprRightType,
						$context
					);
					if ($types !== null) {
						$types = $types->unionWith($leftType);
					} else {
						$types = $leftType;
					}
				}

				if ($types !== null) {
					return $types;
				}
			}

		} elseif ($expr instanceof Node\Expr\BinaryOp\NotIdentical) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BooleanNot(new Node\Expr\BinaryOp\Identical($expr->left, $expr->right)),
				$context
			);
		} elseif ($expr instanceof Node\Expr\BinaryOp\Equal) {
			$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
			if ($expressions !== null) {
				/** @var Expr $exprNode */
				$exprNode = $expressions[0];
				/** @var \PHPStan\Type\ConstantScalarType $constantType */
				$constantType = $expressions[1];
				if ($constantType->getValue() === false || $constantType->getValue() === null) {
					return $this->specifyTypesInCondition(
						$scope,
						$exprNode,
						$context->true() ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createFalsey()->negate()
					);
				}

				if ($constantType->getValue() === true) {
					return $this->specifyTypesInCondition(
						$scope,
						$exprNode,
						$context->true() ? TypeSpecifierContext::createTruthy() : TypeSpecifierContext::createTruthy()->negate()
					);
				}
			}

			$leftType = $scope->getType($expr->left);
			$leftBooleanType = $leftType->toBoolean();
			$rightType = $scope->getType($expr->right);
			if ($leftBooleanType instanceof ConstantBooleanType && $rightType instanceof BooleanType) {
				return $this->specifyTypesInCondition(
					$scope,
					new Expr\BinaryOp\Identical(
						new ConstFetch(new Name($leftBooleanType->getValue() ? 'true' : 'false')),
						$expr->right
					),
					$context
				);
			}

			$rightBooleanType = $rightType->toBoolean();
			if ($rightBooleanType instanceof ConstantBooleanType && $leftType instanceof BooleanType) {
				return $this->specifyTypesInCondition(
					$scope,
					new Expr\BinaryOp\Identical(
						$expr->left,
						new ConstFetch(new Name($rightBooleanType->getValue() ? 'true' : 'false'))
					),
					$context
				);
			}

			if (
				$expr->left instanceof FuncCall
				&& $expr->left->name instanceof Name
				&& strtolower($expr->left->name->toString()) === 'get_class'
				&& isset($expr->left->args[0])
				&& $rightType instanceof ConstantStringType
			) {
				return $this->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$expr->left->args[0]->value,
						new Name($rightType->getValue())
					),
					$context
				);
			}

			if (
				$expr->right instanceof FuncCall
				&& $expr->right->name instanceof Name
				&& strtolower($expr->right->name->toString()) === 'get_class'
				&& isset($expr->right->args[0])
				&& $leftType instanceof ConstantStringType
			) {
				return $this->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$expr->right->args[0]->value,
						new Name($leftType->getValue())
					),
					$context
				);
			}
		} elseif ($expr instanceof Node\Expr\BinaryOp\NotEqual) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BooleanNot(new Node\Expr\BinaryOp\Equal($expr->left, $expr->right)),
				$context
			);

		} elseif ($expr instanceof Node\Expr\BinaryOp\Smaller || $expr instanceof Node\Expr\BinaryOp\SmallerOrEqual) {
			$orEqual = $expr instanceof Node\Expr\BinaryOp\SmallerOrEqual;
			$offset = $orEqual ? 0 : 1;
			$leftType = $scope->getType($expr->left);
			$rightType = $scope->getType($expr->right);

			if (
				$expr->left instanceof FuncCall
				&& count($expr->left->args) === 1
				&& $expr->left->name instanceof Name
				&& strtolower((string) $expr->left->name) === 'count'
				&& $rightType instanceof ConstantIntegerType
			) {
				$inverseOperator = $expr instanceof Node\Expr\BinaryOp\Smaller
					? new Node\Expr\BinaryOp\SmallerOrEqual($expr->right, $expr->left)
					: new Node\Expr\BinaryOp\Smaller($expr->right, $expr->left);

				return $this->specifyTypesInCondition(
					$scope,
					new Node\Expr\BooleanNot($inverseOperator),
					$context
				);
			}

			$result = new SpecifiedTypes();

			if (
				!$context->null()
				&& $expr->right instanceof FuncCall
				&& count($expr->right->args) === 1
				&& $expr->right->name instanceof Name
				&& strtolower((string) $expr->right->name) === 'count'
				&& $leftType instanceof ConstantIntegerType
			) {
				if ($context->truthy() || $leftType->getValue() + $offset === 1) {
					$argType = $scope->getType($expr->right->args[0]->value);
					if ((new ArrayType(new MixedType(), new MixedType()))->isSuperTypeOf($argType)->yes()) {
						$result = $result->unionWith($this->create($expr->right->args[0]->value, new NonEmptyArrayType(), $context));
					}
				}
			}

			if ($leftType instanceof ConstantIntegerType) {
				if ($expr->right instanceof Expr\PostInc) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset + 1),
						$context
					));
				} elseif ($expr->right instanceof Expr\PostDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset - 1),
						$context
					));
				} elseif ($expr->right instanceof Expr\PreInc || $expr->right instanceof Expr\PreDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset),
						$context
					));
				}
			}

			if ($rightType instanceof ConstantIntegerType) {
				if ($expr->left instanceof Expr\PostInc) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset + 1),
						$context
					));
				} elseif ($expr->left instanceof Expr\PostDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset - 1),
						$context
					));
				} elseif ($expr->left instanceof Expr\PreInc || $expr->left instanceof Expr\PreDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset),
						$context
					));
				}
			}

			if ($context->truthy()) {
				if (!$expr->left instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->left,
							$orEqual ? $rightType->getSmallerOrEqualType() : $rightType->getSmallerType(),
							TypeSpecifierContext::createTruthy()
						)
					);
				}
				if (!$expr->right instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->right,
							$orEqual ? $leftType->getGreaterOrEqualType() : $leftType->getGreaterType(),
							TypeSpecifierContext::createTruthy()
						)
					);
				}
			} elseif ($context->falsey()) {
				if (!$expr->left instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->left,
							$orEqual ? $rightType->getGreaterType() : $rightType->getGreaterOrEqualType(),
							TypeSpecifierContext::createTruthy()
						)
					);
				}
				if (!$expr->right instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->right,
							$orEqual ? $leftType->getSmallerType() : $leftType->getSmallerOrEqualType(),
							TypeSpecifierContext::createTruthy()
						)
					);
				}
			}

			return $result;

		} elseif ($expr instanceof Node\Expr\BinaryOp\Greater) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Smaller($expr->right, $expr->left), $context, $defaultHandleFunctions);

		} elseif ($expr instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\SmallerOrEqual($expr->right, $expr->left), $context, $defaultHandleFunctions);

		} elseif ($expr instanceof FuncCall && $expr->name instanceof Name) {
			if ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				foreach ($this->getFunctionTypeSpecifyingExtensions() as $extension) {
					if (!$extension->isFunctionSupported($functionReflection, $expr, $context)) {
						continue;
					}

					return $extension->specifyTypes($functionReflection, $expr, $scope, $context);
				}
			}

			if ($defaultHandleFunctions) {
				return $this->handleDefaultTruthyOrFalseyContext($context, $expr);
			}
		} elseif ($expr instanceof MethodCall && $expr->name instanceof Node\Identifier) {
			$methodCalledOnType = $scope->getType($expr->var);
			$referencedClasses = TypeUtils::getDirectClassNames($methodCalledOnType);
			if (
				count($referencedClasses) === 1
				&& $this->reflectionProvider->hasClass($referencedClasses[0])
			) {
				$methodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
				if ($methodClassReflection->hasMethod($expr->name->name)) {
					$methodReflection = $methodClassReflection->getMethod($expr->name->name, $scope);
					foreach ($this->getMethodTypeSpecifyingExtensionsForClass($methodClassReflection->getName()) as $extension) {
						if (!$extension->isMethodSupported($methodReflection, $expr, $context)) {
							continue;
						}

						return $extension->specifyTypes($methodReflection, $expr, $scope, $context);
					}
				}
			}

			if ($defaultHandleFunctions) {
				return $this->handleDefaultTruthyOrFalseyContext($context, $expr);
			}
		} elseif ($expr instanceof StaticCall && $expr->name instanceof Node\Identifier) {
			if ($expr->class instanceof Name) {
				$calleeType = new ObjectType($scope->resolveName($expr->class));
			} else {
				$calleeType = $scope->getType($expr->class);
			}

			if ($calleeType->hasMethod($expr->name->name)->yes()) {
				$staticMethodReflection = $calleeType->getMethod($expr->name->name, $scope);
				$referencedClasses = TypeUtils::getDirectClassNames($calleeType);
				if (
					count($referencedClasses) === 1
					&& $this->reflectionProvider->hasClass($referencedClasses[0])
				) {
					$staticMethodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
					foreach ($this->getStaticMethodTypeSpecifyingExtensionsForClass($staticMethodClassReflection->getName()) as $extension) {
						if (!$extension->isStaticMethodSupported($staticMethodReflection, $expr, $context)) {
							continue;
						}

						return $extension->specifyTypes($staticMethodReflection, $expr, $scope, $context);
					}
				}
			}

			if ($defaultHandleFunctions) {
				return $this->handleDefaultTruthyOrFalseyContext($context, $expr);
			}
		} elseif ($expr instanceof BooleanAnd || $expr instanceof LogicalAnd) {
			$leftTypes = $this->specifyTypesInCondition($scope, $expr->left, $context);
			$rightTypes = $this->specifyTypesInCondition($scope, $expr->right, $context);
			return $context->true() ? $leftTypes->unionWith($rightTypes) : $leftTypes->intersectWith($rightTypes);
		} elseif ($expr instanceof BooleanOr || $expr instanceof LogicalOr) {
			$leftTypes = $this->specifyTypesInCondition($scope, $expr->left, $context);
			$rightTypes = $this->specifyTypesInCondition($scope, $expr->right, $context);
			return $context->true() ? $leftTypes->intersectWith($rightTypes) : $leftTypes->unionWith($rightTypes);
		} elseif ($expr instanceof Node\Expr\BooleanNot && !$context->null()) {
			return $this->specifyTypesInCondition($scope, $expr->expr, $context->negate());
		} elseif ($expr instanceof Node\Expr\Assign) {
			if (!$scope instanceof MutatingScope) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if ($context->null()) {
				return $this->specifyTypesInCondition($scope->exitFirstLevelStatements(), $expr->expr, $context);
			}

			return $this->specifyTypesInCondition($scope->exitFirstLevelStatements(), $expr->var, $context);
		} elseif (
			(
				$expr instanceof Expr\Isset_
				&& count($expr->vars) > 0
				&& $context->true()
			)
			|| ($expr instanceof Expr\Empty_ && $context->false())
		) {
			$vars = [];
			if ($expr instanceof Expr\Isset_) {
				$varsToIterate = $expr->vars;
			} else {
				$varsToIterate = [$expr->expr];
			}
			foreach ($varsToIterate as $var) {
				$tmpVars = [$var];

				while (
					$var instanceof ArrayDimFetch
					|| $var instanceof PropertyFetch
					|| (
						$var instanceof StaticPropertyFetch
						&& $var->class instanceof Expr
					)
				) {
					if ($var instanceof StaticPropertyFetch) {
						/** @var Expr $var */
						$var = $var->class;
					} else {
						$var = $var->var;
					}
					$tmpVars[] = $var;
				}

				$vars = array_merge($vars, array_reverse($tmpVars));
			}

			if (count($vars) === 0) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$types = null;
			foreach ($vars as $var) {
				if ($var instanceof Expr\Variable && is_string($var->name)) {
					if ($scope->hasVariableType($var->name)->no()) {
						return new SpecifiedTypes([], []);
					}
				}
				if ($expr instanceof Expr\Isset_) {
					if (
						$var instanceof ArrayDimFetch
						&& $var->dim !== null
						&& !$scope->getType($var->var) instanceof MixedType
					) {
						$type = $this->create(
							$var->var,
							new HasOffsetType($scope->getType($var->dim)),
							$context
						)->unionWith(
							$this->create($var, new NullType(), TypeSpecifierContext::createFalse())
						);
					} else {
						$type = $this->create($var, new NullType(), TypeSpecifierContext::createFalse());
					}
				} else {
					$type = $this->create(
						$var,
						new UnionType([
							new NullType(),
							new ConstantBooleanType(false),
						]),
						TypeSpecifierContext::createFalse()
					);
				}

				if (
					$var instanceof PropertyFetch
					&& $var->name instanceof Node\Identifier
				) {
					$type = $type->unionWith($this->create($var->var, new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType($var->name->toString()),
					]), TypeSpecifierContext::createTruthy()));
				} elseif (
					$var instanceof StaticPropertyFetch
					&& $var->class instanceof Expr
					&& $var->name instanceof Node\VarLikeIdentifier
				) {
					$type = $type->unionWith($this->create($var->class, new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType($var->name->toString()),
					]), TypeSpecifierContext::createTruthy()));
				}

				if ($types === null) {
					$types = $type;
				} else {
					$types = $types->unionWith($type);
				}
			}

			if (
				$expr instanceof Expr\Empty_
				&& (new ArrayType(new MixedType(), new MixedType()))->isSuperTypeOf($scope->getType($expr->expr))->yes()) {
				$types = $types->unionWith(
					$this->create($expr->expr, new NonEmptyArrayType(), $context->negate())
				);
			}

			return $types;
		} elseif (
			$expr instanceof Expr\Empty_ && $context->truthy()
			&& (new ArrayType(new MixedType(), new MixedType()))->isSuperTypeOf($scope->getType($expr->expr))->yes()
		) {
			return $this->create($expr->expr, new NonEmptyArrayType(), $context->negate());
		} elseif ($expr instanceof Expr\ErrorSuppress) {
			return $this->specifyTypesInCondition($scope, $expr->expr, $context, $defaultHandleFunctions);
		} elseif ($expr instanceof Expr\NullsafePropertyFetch && !$context->null()) {
			$types = $this->specifyTypesInCondition(
				$scope,
				new BooleanAnd(
					new Expr\BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					new PropertyFetch($expr->var, $expr->name)
				),
				$context,
				$defaultHandleFunctions
			);

			$nullSafeTypes = $this->handleDefaultTruthyOrFalseyContext($context, $expr);
			return $context->true() ? $types->unionWith($nullSafeTypes) : $types->intersectWith($nullSafeTypes);
		} elseif (!$context->null()) {
			return $this->handleDefaultTruthyOrFalseyContext($context, $expr);
		}

		return new SpecifiedTypes();
	}

	private function handleDefaultTruthyOrFalseyContext(TypeSpecifierContext $context, Expr $expr): SpecifiedTypes
	{
		if (!$context->truthy()) {
			$type = StaticTypeFactory::truthy();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse());
		} elseif (!$context->falsey()) {
			$type = StaticTypeFactory::falsey();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse());
		}

		return new SpecifiedTypes();
	}

	/**
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PhpParser\Node\Expr\BinaryOp $binaryOperation
	 * @return (Expr|\PHPStan\Type\ConstantScalarType)[]|null
	 */
	private function findTypeExpressionsFromBinaryOperation(Scope $scope, Node\Expr\BinaryOp $binaryOperation): ?array
	{
		$leftType = $scope->getType($binaryOperation->left);
		$rightType = $scope->getType($binaryOperation->right);
		if (
			$leftType instanceof \PHPStan\Type\ConstantScalarType
			&& !$binaryOperation->right instanceof ConstFetch
			&& !$binaryOperation->right instanceof Expr\ClassConstFetch
		) {
			return [$binaryOperation->right, $leftType];
		} elseif (
			$rightType instanceof \PHPStan\Type\ConstantScalarType
			&& !$binaryOperation->left instanceof ConstFetch
			&& !$binaryOperation->left instanceof Expr\ClassConstFetch
		) {
			return [$binaryOperation->left, $rightType];
		}

		return null;
	}

	public function create(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		bool $overwrite = false
	): SpecifiedTypes
	{
		if ($expr instanceof New_ || $expr instanceof Instanceof_) {
			return new SpecifiedTypes();
		}

		$sureTypes = [];
		$sureNotTypes = [];

		$exprString = $this->printer->prettyPrintExpr($expr);
		if ($context->false()) {
			$sureNotTypes[$exprString] = [$expr, $type];
		} elseif ($context->true()) {
			$sureTypes[$exprString] = [$expr, $type];
		}

		$types = new SpecifiedTypes($sureTypes, $sureNotTypes, $overwrite);
		if ($expr instanceof Expr\NullsafePropertyFetch && !$context->null()) {
			$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), $type, $context);
			if (
				$context->true() && !TypeCombinator::containsNull($type)
			) {
				$propertyFetchTypes = $propertyFetchTypes->unionWith(
					$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse())
				);
			} elseif ($context->false() && TypeCombinator::containsNull($type)) {
				$propertyFetchTypes = $propertyFetchTypes->unionWith(
					$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse())
				);
			}
			return $types->unionWith($propertyFetchTypes);
		}

		return $types;
	}

	private function createRangeTypes(Expr $expr, Type $type, TypeSpecifierContext $context): SpecifiedTypes
	{
		$sureNotTypes = [];

		if ($type instanceof IntegerRangeType || $type instanceof ConstantIntegerType) {
			$exprString = $this->printer->prettyPrintExpr($expr);
			if ($context->false()) {
				$sureNotTypes[$exprString] = [$expr, $type];
			} elseif ($context->true()) {
				$inverted = TypeCombinator::remove(new IntegerType(), $type);
				$sureNotTypes[$exprString] = [$expr, $inverted];
			}
		}

		return new SpecifiedTypes([], $sureNotTypes);
	}

	/**
	 * @return \PHPStan\Type\FunctionTypeSpecifyingExtension[]
	 */
	private function getFunctionTypeSpecifyingExtensions(): array
	{
		return $this->functionTypeSpecifyingExtensions;
	}

	/**
	 * @param string $className
	 * @return \PHPStan\Type\MethodTypeSpecifyingExtension[]
	 */
	private function getMethodTypeSpecifyingExtensionsForClass(string $className): array
	{
		if ($this->methodTypeSpecifyingExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->methodTypeSpecifyingExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->methodTypeSpecifyingExtensionsByClass = $byClass;
		}
		return $this->getTypeSpecifyingExtensionsForType($this->methodTypeSpecifyingExtensionsByClass, $className);
	}

	/**
	 * @param string $className
	 * @return \PHPStan\Type\StaticMethodTypeSpecifyingExtension[]
	 */
	private function getStaticMethodTypeSpecifyingExtensionsForClass(string $className): array
	{
		if ($this->staticMethodTypeSpecifyingExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->staticMethodTypeSpecifyingExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->staticMethodTypeSpecifyingExtensionsByClass = $byClass;
		}
		return $this->getTypeSpecifyingExtensionsForType($this->staticMethodTypeSpecifyingExtensionsByClass, $className);
	}

	/**
	 * @param \PHPStan\Type\MethodTypeSpecifyingExtension[][]|\PHPStan\Type\StaticMethodTypeSpecifyingExtension[][] $extensions
	 * @param string $className
	 * @return mixed[]
	 */
	private function getTypeSpecifyingExtensionsForType(array $extensions, string $className): array
	{
		$extensionsForClass = [[]];
		$class = $this->reflectionProvider->getClass($className);
		foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
			if (!isset($extensions[$extensionClassName])) {
				continue;
			}

			$extensionsForClass[] = $extensions[$extensionClassName];
		}

		return array_merge(...$extensionsForClass);
	}

}
