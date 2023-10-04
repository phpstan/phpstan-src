<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\NotIssetExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reverse;
use function count;
use function in_array;
use function is_string;
use function strtolower;
use function substr;

class TypeSpecifier
{

	/** @var MethodTypeSpecifyingExtension[][]|null */
	private ?array $methodTypeSpecifyingExtensionsByClass = null;

	/** @var StaticMethodTypeSpecifyingExtension[][]|null */
	private ?array $staticMethodTypeSpecifyingExtensionsByClass = null;

	/**
	 * @param FunctionTypeSpecifyingExtension[] $functionTypeSpecifyingExtensions
	 * @param MethodTypeSpecifyingExtension[] $methodTypeSpecifyingExtensions
	 * @param StaticMethodTypeSpecifyingExtension[] $staticMethodTypeSpecifyingExtensions
	 */
	public function __construct(
		private ExprPrinter $exprPrinter,
		private ReflectionProvider $reflectionProvider,
		private array $functionTypeSpecifyingExtensions,
		private array $methodTypeSpecifyingExtensions,
		private array $staticMethodTypeSpecifyingExtensions,
		private bool $rememberPossiblyImpureFunctionValues,
	)
	{
		foreach (array_merge($functionTypeSpecifyingExtensions, $methodTypeSpecifyingExtensions, $staticMethodTypeSpecifyingExtensions) as $extension) {
			if (!($extension instanceof TypeSpecifierAwareExtension)) {
				continue;
			}

			$extension->setTypeSpecifier($this);
		}
	}

	/** @api */
	public function specifyTypesInCondition(
		Scope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
		?Expr $rootExpr = null,
	): SpecifiedTypes
	{
		$rootExpr ??= $expr;

		if ($expr instanceof Expr\CallLike && $expr->isFirstClassCallable()) {
			return new SpecifiedTypes([], [], false, [], $rootExpr);
		}

		if ($expr instanceof Instanceof_) {
			$exprNode = $expr->expr;
			if ($expr->class instanceof Name) {
				$className = (string) $expr->class;
				$lowercasedClassName = strtolower($className);
				if ($lowercasedClassName === 'self' && $scope->isInClass()) {
					$type = new ObjectType($scope->getClassReflection()->getName());
				} elseif ($lowercasedClassName === 'static' && $scope->isInClass()) {
					$type = new StaticType($scope->getClassReflection());
				} elseif ($lowercasedClassName === 'parent') {
					if (
						$scope->isInClass()
						&& $scope->getClassReflection()->getParentClass() !== null
					) {
						$type = new ObjectType($scope->getClassReflection()->getParentClass()->getName());
					} else {
						$type = new NonexistentParentClassType();
					}
				} else {
					$type = new ObjectType($className);
				}
				return $this->create($exprNode, $type, $context, false, $scope, $rootExpr);
			}

			$classType = $scope->getType($expr->class);
			$type = TypeTraverser::map($classType, static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type->getObjectClassNames() !== []) {
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
						new ObjectWithoutClassType(),
					);
					return $this->create($exprNode, $type, $context, false, $scope, $rootExpr);
				} elseif ($context->false()) {
					$exprType = $scope->getType($expr->expr);
					if (!$type->isSuperTypeOf($exprType)->yes()) {
						return $this->create($exprNode, $type, $context, false, $scope, $rootExpr);
					}
				}
			}
			if ($context->true()) {
				return $this->create($exprNode, new ObjectWithoutClassType(), $context, false, $scope, $rootExpr);
			}
		} elseif ($expr instanceof Node\Expr\BinaryOp\Identical) {
			return $this->resolveIdentical($expr, $scope, $context, $rootExpr);

		} elseif ($expr instanceof Node\Expr\BinaryOp\NotIdentical) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BooleanNot(new Node\Expr\BinaryOp\Identical($expr->left, $expr->right)),
				$context,
				$rootExpr,
			);
		} elseif ($expr instanceof Node\Expr\BinaryOp\Equal) {
			$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
			if ($expressions !== null) {
				$exprNode = $expressions[0];
				$constantType = $expressions[1];
				if (!$context->null() && ($constantType->getValue() === false || $constantType->getValue() === null)) {
					return $this->specifyTypesInCondition(
						$scope,
						$exprNode,
						$context->true() ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createFalsey()->negate(),
						$rootExpr,
					);
				}

				if (!$context->null() && $constantType->getValue() === true) {
					return $this->specifyTypesInCondition(
						$scope,
						$exprNode,
						$context->true() ? TypeSpecifierContext::createTruthy() : TypeSpecifierContext::createTruthy()->negate(),
						$rootExpr,
					);
				}

				if (
					$exprNode instanceof FuncCall
					&& $exprNode->name instanceof Name
					&& strtolower($exprNode->name->toString()) === 'gettype'
					&& isset($exprNode->getArgs()[0])
					&& $constantType->isString()->yes()
				) {
					return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context, $rootExpr);
				}

				if (
					$exprNode instanceof FuncCall
					&& $exprNode->name instanceof Name
					&& strtolower($exprNode->name->toString()) === 'get_class'
					&& isset($exprNode->getArgs()[0])
					&& $constantType->isString()->yes()
				) {
					return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context, $rootExpr);
				}
			}

			$leftType = $scope->getType($expr->left);
			$rightType = $scope->getType($expr->right);

			$leftBooleanType = $leftType->toBoolean();
			if ($leftBooleanType instanceof ConstantBooleanType && $rightType->isBoolean()->yes()) {
				return $this->specifyTypesInCondition(
					$scope,
					new Expr\BinaryOp\Identical(
						new ConstFetch(new Name($leftBooleanType->getValue() ? 'true' : 'false')),
						$expr->right,
					),
					$context,
					$rootExpr,
				);
			}

			$rightBooleanType = $rightType->toBoolean();
			if ($rightBooleanType instanceof ConstantBooleanType && $leftType->isBoolean()->yes()) {
				return $this->specifyTypesInCondition(
					$scope,
					new Expr\BinaryOp\Identical(
						$expr->left,
						new ConstFetch(new Name($rightBooleanType->getValue() ? 'true' : 'false')),
					),
					$context,
					$rootExpr,
				);
			}

			if (
				!$context->null()
				&& $rightType->isArray()->yes()
				&& $leftType->isConstantArray()->yes() && $leftType->isIterableAtLeastOnce()->no()
			) {
				return $this->create($expr->right, new NonEmptyArrayType(), $context->negate(), false, $scope, $rootExpr);
			}

			if (
				!$context->null()
				&& $leftType->isArray()->yes()
				&& $rightType->isConstantArray()->yes() && $rightType->isIterableAtLeastOnce()->no()
			) {
				return $this->create($expr->left, new NonEmptyArrayType(), $context->negate(), false, $scope, $rootExpr);
			}

			$integerType = new IntegerType();
			$floatType = new FloatType();
			if (
				($leftType->isString()->yes() && $rightType->isString()->yes())
				|| ($integerType->isSuperTypeOf($leftType)->yes() && $integerType->isSuperTypeOf($rightType)->yes())
				|| ($floatType->isSuperTypeOf($leftType)->yes() && $floatType->isSuperTypeOf($rightType)->yes())
				|| ($leftType->isEnum()->yes() && $rightType->isEnum()->yes())
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context, $rootExpr);
			}

			$leftExprString = $this->exprPrinter->printExpr($expr->left);
			$rightExprString = $this->exprPrinter->printExpr($expr->right);
			if ($leftExprString === $rightExprString) {
				if (!$expr->left instanceof Expr\Variable || !$expr->right instanceof Expr\Variable) {
					return new SpecifiedTypes([], [], false, [], $rootExpr);
				}
			}

			$leftTypes = $this->create($expr->left, $leftType, $context, false, $scope, $rootExpr);
			$rightTypes = $this->create($expr->right, $rightType, $context, false, $scope, $rootExpr);

			return $context->true()
				? $leftTypes->unionWith($rightTypes)
				: $leftTypes->normalize($scope)->intersectWith($rightTypes->normalize($scope));
		} elseif ($expr instanceof Node\Expr\BinaryOp\NotEqual) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BooleanNot(new Node\Expr\BinaryOp\Equal($expr->left, $expr->right)),
				$context,
				$rootExpr,
			);

		} elseif ($expr instanceof Node\Expr\BinaryOp\Smaller || $expr instanceof Node\Expr\BinaryOp\SmallerOrEqual) {

			if (
				$expr->left instanceof FuncCall
				&& count($expr->left->getArgs()) === 1
				&& $expr->left->name instanceof Name
				&& in_array(strtolower((string) $expr->left->name), ['count', 'sizeof', 'strlen'], true)
				&& (
					!$expr->right instanceof FuncCall
					|| !$expr->right->name instanceof Name
					|| !in_array(strtolower((string) $expr->right->name), ['count', 'sizeof', 'strlen'], true)
				)
			) {
				$inverseOperator = $expr instanceof Node\Expr\BinaryOp\Smaller
					? new Node\Expr\BinaryOp\SmallerOrEqual($expr->right, $expr->left)
					: new Node\Expr\BinaryOp\Smaller($expr->right, $expr->left);

				return $this->specifyTypesInCondition(
					$scope,
					new Node\Expr\BooleanNot($inverseOperator),
					$context,
					$rootExpr,
				);
			}

			$orEqual = $expr instanceof Node\Expr\BinaryOp\SmallerOrEqual;
			$offset = $orEqual ? 0 : 1;
			$leftType = $scope->getType($expr->left);
			$result = new SpecifiedTypes([], [], false, [], $rootExpr);

			if (
				!$context->null()
				&& $expr->right instanceof FuncCall
				&& count($expr->right->getArgs()) === 1
				&& $expr->right->name instanceof Name
				&& in_array(strtolower((string) $expr->right->name), ['count', 'sizeof'], true)
				&& (new IntegerType())->isSuperTypeOf($leftType)->yes()
			) {
				if (
					$context->truthy() && (IntegerRangeType::createAllGreaterThanOrEqualTo(1 - $offset)->isSuperTypeOf($leftType)->yes())
					|| ($context->falsey() && (new ConstantIntegerType(1 - $offset))->isSuperTypeOf($leftType)->yes())
				) {
					$argType = $scope->getType($expr->right->getArgs()[0]->value);
					if ($argType->isArray()->yes()) {
						$result = $result->unionWith($this->create($expr->right->getArgs()[0]->value, new NonEmptyArrayType(), $context, false, $scope, $rootExpr));
					}
				}
			}

			if (
				!$context->null()
				&& $expr->right instanceof FuncCall
				&& count($expr->right->getArgs()) === 1
				&& $expr->right->name instanceof Name
				&& strtolower((string) $expr->right->name) === 'strlen'
				&& (new IntegerType())->isSuperTypeOf($leftType)->yes()
			) {
				if (
					$context->truthy() && (IntegerRangeType::createAllGreaterThanOrEqualTo(1 - $offset)->isSuperTypeOf($leftType)->yes())
					|| ($context->falsey() && (new ConstantIntegerType(1 - $offset))->isSuperTypeOf($leftType)->yes())
				) {
					$argType = $scope->getType($expr->right->getArgs()[0]->value);
					if ($argType->isString()->yes()) {
						$accessory = new AccessoryNonEmptyStringType();
						if ($leftType instanceof ConstantIntegerType && $leftType->getValue() >= 2) {
							$accessory = new AccessoryNonFalsyStringType();
						}

						$result = $result->unionWith($this->create($expr->right->getArgs()[0]->value, $accessory, $context, false, $scope, $rootExpr));
					}
				}
			}

			if ($leftType instanceof ConstantIntegerType) {
				if ($expr->right instanceof Expr\PostInc) {
					$result = $result->unionWith($this->createRangeTypes(
						$rootExpr,
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset + 1),
						$context,
					));
				} elseif ($expr->right instanceof Expr\PostDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$rootExpr,
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset - 1),
						$context,
					));
				} elseif ($expr->right instanceof Expr\PreInc || $expr->right instanceof Expr\PreDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$rootExpr,
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset),
						$context,
					));
				}
			}

			$rightType = $scope->getType($expr->right);
			if ($rightType instanceof ConstantIntegerType) {
				if ($expr->left instanceof Expr\PostInc) {
					$result = $result->unionWith($this->createRangeTypes(
						$rootExpr,
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset + 1),
						$context,
					));
				} elseif ($expr->left instanceof Expr\PostDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$rootExpr,
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset - 1),
						$context,
					));
				} elseif ($expr->left instanceof Expr\PreInc || $expr->left instanceof Expr\PreDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$rootExpr,
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset),
						$context,
					));
				}
			}

			if ($context->true()) {
				if (!$expr->left instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->left,
							$orEqual ? $rightType->getSmallerOrEqualType() : $rightType->getSmallerType(),
							TypeSpecifierContext::createTruthy(),
							false,
							$scope,
							$rootExpr,
						),
					);
				}
				if (!$expr->right instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->right,
							$orEqual ? $leftType->getGreaterOrEqualType() : $leftType->getGreaterType(),
							TypeSpecifierContext::createTruthy(),
							false,
							$scope,
							$rootExpr,
						),
					);
				}
			} elseif ($context->false()) {
				if (!$expr->left instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->left,
							$orEqual ? $rightType->getGreaterType() : $rightType->getGreaterOrEqualType(),
							TypeSpecifierContext::createTruthy(),
							false,
							$scope,
							$rootExpr,
						),
					);
				}
				if (!$expr->right instanceof Node\Scalar) {
					$result = $result->unionWith(
						$this->create(
							$expr->right,
							$orEqual ? $leftType->getSmallerType() : $leftType->getSmallerOrEqualType(),
							TypeSpecifierContext::createTruthy(),
							false,
							$scope,
							$rootExpr,
						),
					);
				}
			}

			return $result;

		} elseif ($expr instanceof Node\Expr\BinaryOp\Greater) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Smaller($expr->right, $expr->left), $context, $rootExpr);

		} elseif ($expr instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\SmallerOrEqual($expr->right, $expr->left), $context, $rootExpr);

		} elseif ($expr instanceof FuncCall && $expr->name instanceof Name) {
			if ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				foreach ($this->getFunctionTypeSpecifyingExtensions() as $extension) {
					if (!$extension->isFunctionSupported($functionReflection, $expr, $context)) {
						continue;
					}

					return $extension->specifyTypes($functionReflection, $expr, $scope, $context);
				}

				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $expr->getArgs(), $functionReflection->getVariants());
				if (count($expr->getArgs()) > 0) {
					$specifiedTypes = $this->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$asserts = $functionReflection->getAsserts()->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$parametersAcceptor->getResolvedTemplateTypeMap(),
					$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));
				$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $rootExpr, $expr, $scope);
		} elseif ($expr instanceof MethodCall && $expr->name instanceof Node\Identifier) {
			$methodCalledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($methodCalledOnType, $expr->name->name);
			if ($methodReflection !== null) {
				$referencedClasses = $methodCalledOnType->getObjectClassNames();
				if (
					count($referencedClasses) === 1
					&& $this->reflectionProvider->hasClass($referencedClasses[0])
				) {
					$methodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
					foreach ($this->getMethodTypeSpecifyingExtensionsForClass($methodClassReflection->getName()) as $extension) {
						if (!$extension->isMethodSupported($methodReflection, $expr, $context)) {
							continue;
						}

						return $extension->specifyTypes($methodReflection, $expr, $scope, $context);
					}
				}

				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $expr->getArgs(), $methodReflection->getVariants());
				if (count($expr->getArgs()) > 0) {
					$specifiedTypes = $this->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$asserts = $methodReflection->getAsserts()->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$parametersAcceptor->getResolvedTemplateTypeMap(),
					$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));
				$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $rootExpr, $expr, $scope);
		} elseif ($expr instanceof StaticCall && $expr->name instanceof Node\Identifier) {
			if ($expr->class instanceof Name) {
				$calleeType = $scope->resolveTypeByName($expr->class);
			} else {
				$calleeType = $scope->getType($expr->class);
			}

			$staticMethodReflection = $scope->getMethodReflection($calleeType, $expr->name->name);
			if ($staticMethodReflection !== null) {
				$referencedClasses = $calleeType->getObjectClassNames();
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

				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $expr->getArgs(), $staticMethodReflection->getVariants());
				if (count($expr->getArgs()) > 0) {
					$specifiedTypes = $this->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$asserts = $staticMethodReflection->getAsserts()->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$parametersAcceptor->getResolvedTemplateTypeMap(),
					$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));
				$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $rootExpr, $expr, $scope);
		} elseif ($expr instanceof BooleanAnd || $expr instanceof LogicalAnd) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}
			$leftTypes = $this->specifyTypesInCondition($scope, $expr->left, $context, $rootExpr);
			$rightScope = $scope->filterByTruthyValue($expr->left);
			$rightTypes = $this->specifyTypesInCondition($rightScope, $expr->right, $context, $rootExpr);
			$types = $context->true() ? $leftTypes->unionWith($rightTypes) : $leftTypes->normalize($scope)->intersectWith($rightTypes->normalize($rightScope));
			if ($context->false()) {
				return new SpecifiedTypes(
					$types->getSureTypes(),
					$types->getSureNotTypes(),
					false,
					array_merge(
						$this->processBooleanConditionalTypes($scope, $leftTypes, $rightTypes),
						$this->processBooleanConditionalTypes($scope, $rightTypes, $leftTypes),
					),
					$rootExpr,
				);
			}

			return $types;
		} elseif ($expr instanceof BooleanOr || $expr instanceof LogicalOr) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}
			$leftTypes = $this->specifyTypesInCondition($scope, $expr->left, $context, $rootExpr);
			$rightScope = $scope->filterByFalseyValue($expr->left);
			$rightTypes = $this->specifyTypesInCondition($rightScope, $expr->right, $context, $rootExpr);
			$types = $context->true() ? $leftTypes->normalize($scope)->intersectWith($rightTypes->normalize($rightScope)) : $leftTypes->unionWith($rightTypes);
			if ($context->true()) {
				return new SpecifiedTypes(
					$types->getSureTypes(),
					$types->getSureNotTypes(),
					false,
					array_merge(
						$this->processBooleanConditionalTypes($scope, $leftTypes, $rightTypes),
						$this->processBooleanConditionalTypes($scope, $rightTypes, $leftTypes),
					),
					$rootExpr,
				);
			}

			return $types;
		} elseif ($expr instanceof Node\Expr\BooleanNot && !$context->null()) {
			return $this->specifyTypesInCondition($scope, $expr->expr, $context->negate(), $rootExpr);
		} elseif ($expr instanceof Node\Expr\Assign) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}
			if ($context->null()) {
				return $this->specifyTypesInCondition($scope->exitFirstLevelStatements(), $expr->expr, $context, $rootExpr);
			}

			return $this->specifyTypesInCondition($scope->exitFirstLevelStatements(), $expr->var, $context, $rootExpr);
		} elseif (
			$expr instanceof Expr\Isset_
			&& count($expr->vars) > 0
			&& !$context->null()
		) {
			if (!$context->true()) {
				if (!$scope instanceof MutatingScope) {
					throw new ShouldNotHappenException();
				}

				$specifiedTypes = new SpecifiedTypes();
				foreach ($expr->vars as $var) {
					$isset = $scope->issetCheck($var, static fn () => true);

					// variable is always defined
					if ($isset === true) {
						$specifiedTypes = $specifiedTypes->unionWith($this->specifyTypesInCondition($scope, $var, $context, $rootExpr));
						continue;
					}

					if (!($var instanceof ArrayDimFetch)
						|| $var->dim === null
					) {
						continue;
					}

					// variable is always undefined
					if (
						$isset === false
					) {
						continue;
					}

					// variable maybe be defined
					$type = $scope->getType($var->var);
					if ($type instanceof MixedType) {
						continue;
					}

					$dimType = $scope->getType($var->dim);
					if (!($dimType instanceof ConstantIntegerType) && !($dimType instanceof ConstantStringType)) {
						continue;
					}

					$hasOffsetType = $type->hasOffsetValueType($dimType);
					$offsetType = $type->getOffsetValueType($dimType);
					if ($hasOffsetType->yes() && !TypeCombinator::containsNull($offsetType)) {
						$specifiedTypes = $specifiedTypes->unionWith($this->create(
							new NotIssetExpr($var),
							new ErrorType(),
							$context,
							false,
							$scope,
							$rootExpr,
						));
						continue;
					}

					if (TypeCombinator::containsNull($offsetType)) {
						$nullOffsetArray = ConstantArrayTypeBuilder::createEmpty();
						$nullOffsetArray->setOffsetValueType($dimType, new NullType(), true);

						$specifiedTypes = $specifiedTypes->unionWith($this->create(
							$var->var,
							$nullOffsetArray->getArray(),
							$context->negate(),
							false,
							$scope,
							$rootExpr,
						));

						continue;
					}

					$specifiedTypes = $specifiedTypes->unionWith($this->create(
						$var->var,
						new HasOffsetType($dimType),
						$context,
						false,
						$scope,
						$rootExpr,
					));
				}

				return $specifiedTypes;
			}

			$vars = [];
			foreach ($expr->vars as $var) {
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

			$types = null;
			foreach ($vars as $var) {
				$type = new SpecifiedTypes();

				if ($var instanceof Expr\Variable && is_string($var->name)) {
					if ($scope->hasVariableType($var->name)->no()) {
						return new SpecifiedTypes([], [], false, [], $rootExpr);
					}
				}

				if (
					$var instanceof ArrayDimFetch
					&& $var->dim !== null
					&& !$scope->getType($var->var) instanceof MixedType
				) {
					$dimType = $scope->getType($var->dim);

					if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
						$type = $this->create(
							$var->var,
							new HasOffsetType($dimType),
							$context,
							false,
							$scope,
							$rootExpr,
						);
					}
				}

				if (
					$var instanceof PropertyFetch
					&& $var->name instanceof Node\Identifier
				) {
					$type = $this->create($var->var, new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType($var->name->toString()),
					]), TypeSpecifierContext::createTruthy(), false, $scope, $rootExpr);
				} elseif (
					$var instanceof StaticPropertyFetch
					&& $var->class instanceof Expr
					&& $var->name instanceof Node\VarLikeIdentifier
				) {
					$type = $this->create($var->class, new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType($var->name->toString()),
					]), TypeSpecifierContext::createTruthy(), false, $scope, $rootExpr);
				}

				$type = $type->unionWith(
					$this->create($var, new NullType(), TypeSpecifierContext::createFalse(), false, $scope, $rootExpr),
				);

				if ($types === null) {
					$types = $type;
				} else {
					$types = $types->unionWith($type);
				}
			}

			return $types;
		} elseif (
			$expr instanceof Expr\BinaryOp\Coalesce
			&& $context->true()
			&& ((new ConstantBooleanType(false))->isSuperTypeOf($scope->getType($expr->right))->yes())
		) {
			return $this->create(
				$expr->left,
				new NullType(),
				TypeSpecifierContext::createFalse(),
				false,
				$scope,
				$rootExpr,
			);
		} elseif (
			$expr instanceof Expr\Empty_
		) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}

			$isset = $scope->issetCheck($expr->expr, static fn () => true);
			if ($isset === false) {
				return new SpecifiedTypes();
			}

			return $this->specifyTypesInCondition($scope, new BooleanOr(
				new Expr\BooleanNot(new Expr\Isset_([$expr->expr])),
				new Expr\BooleanNot($expr->expr),
			), $context, $rootExpr);
		} elseif ($expr instanceof Expr\ErrorSuppress) {
			return $this->specifyTypesInCondition($scope, $expr->expr, $context, $rootExpr);
		} elseif (
			$expr instanceof Expr\Ternary
			&& !$context->null()
			&& ((new ConstantBooleanType(false))->isSuperTypeOf($scope->getType($expr->else))->yes())
		) {
			$conditionExpr = $expr->cond;
			if ($expr->if !== null) {
				$conditionExpr = new BooleanAnd($conditionExpr, $expr->if);
			}

			return $this->specifyTypesInCondition($scope, $conditionExpr, $context, $rootExpr);

		} elseif ($expr instanceof Expr\NullsafePropertyFetch && !$context->null()) {
			$types = $this->specifyTypesInCondition(
				$scope,
				new BooleanAnd(
					new Expr\BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					new PropertyFetch($expr->var, $expr->name),
				),
				$context,
				$rootExpr,
			);

			$nullSafeTypes = $this->handleDefaultTruthyOrFalseyContext($context, $rootExpr, $expr, $scope);
			return $context->true() ? $types->unionWith($nullSafeTypes) : $types->normalize($scope)->intersectWith($nullSafeTypes->normalize($scope));
		} elseif ($expr instanceof Expr\NullsafeMethodCall && !$context->null()) {
			$types = $this->specifyTypesInCondition(
				$scope,
				new BooleanAnd(
					new Expr\BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					new MethodCall($expr->var, $expr->name, $expr->args),
				),
				$context,
				$rootExpr,
			);

			$nullSafeTypes = $this->handleDefaultTruthyOrFalseyContext($context, $rootExpr, $expr, $scope);
			return $context->true() ? $types->unionWith($nullSafeTypes) : $types->normalize($scope)->intersectWith($nullSafeTypes->normalize($scope));
		} elseif (!$context->null()) {
			return $this->handleDefaultTruthyOrFalseyContext($context, $rootExpr, $expr, $scope);
		}

		return new SpecifiedTypes([], [], false, [], $rootExpr);
	}

	private function specifyTypesForConstantBinaryExpression(
		Expr $exprNode,
		ConstantScalarType $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		?Expr $rootExpr,
	): ?SpecifiedTypes
	{
		if (!$context->null() && $constantType->getValue() === false) {
			$types = $this->create($exprNode, $constantType, $context, false, $scope, $rootExpr);
			if ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch) {
				return $types;
			}

			return $types->unionWith($this->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createFalse()->negate(),
				$rootExpr,
			));
		}

		if (!$context->null() && $constantType->getValue() === true) {
			$types = $this->create($exprNode, $constantType, $context, false, $scope, $rootExpr);
			if ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch) {
				return $types;
			}

			return $types->unionWith($this->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createTrue()->negate(),
				$rootExpr,
			));
		}

		if ($constantType->getValue() === null) {
			return $this->create($exprNode, $constantType, $context, false, $scope, $rootExpr);
		}

		if (
			!$context->null()
			&& $exprNode instanceof FuncCall
			&& count($exprNode->getArgs()) === 1
			&& $exprNode->name instanceof Name
			&& in_array(strtolower((string) $exprNode->name), ['count', 'sizeof'], true)
			&& $constantType instanceof ConstantIntegerType
		) {
			if ($context->truthy() || $constantType->getValue() === 0) {
				$newContext = $context;
				if ($constantType->getValue() === 0) {
					$newContext = $newContext->negate();
				}
				$argType = $scope->getType($exprNode->getArgs()[0]->value);
				if ($argType->isArray()->yes()) {
					$funcTypes = $this->create($exprNode, $constantType, $context, false, $scope, $rootExpr);
					if ($argType->isList()->yes() && $context->truthy() && $constantType->getValue() < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
						$valueTypesBuilder = ConstantArrayTypeBuilder::createEmpty();
						$itemType = $argType->getIterableValueType();
						for ($i = 0; $i < $constantType->getValue(); $i++) {
							$valueTypesBuilder->setOffsetValueType(new ConstantIntegerType($i), $itemType);
						}
						$valueTypes = $this->create($exprNode->getArgs()[0]->value, $valueTypesBuilder->getArray(), $context, false, $scope, $rootExpr);
					} else {
						$valueTypes = $this->create($exprNode->getArgs()[0]->value, new NonEmptyArrayType(), $newContext, false, $scope, $rootExpr);
					}
					return $funcTypes->unionWith($valueTypes);
				}
			}
		}

		if (
			!$context->null()
			&& $exprNode instanceof FuncCall
			&& count($exprNode->getArgs()) === 1
			&& $exprNode->name instanceof Name
			&& strtolower((string) $exprNode->name) === 'strlen'
			&& $constantType instanceof ConstantIntegerType
		) {
			if ($context->truthy() || $constantType->getValue() === 0) {
				$newContext = $context;
				if ($constantType->getValue() === 0) {
					$newContext = $newContext->negate();
				}
				$argType = $scope->getType($exprNode->getArgs()[0]->value);
				if ($argType->isString()->yes()) {
					$funcTypes = $this->create($exprNode, $constantType, $context, false, $scope, $rootExpr);

					$accessory = new AccessoryNonEmptyStringType();
					if ($constantType->getValue() >= 2) {
						$accessory = new AccessoryNonFalsyStringType();
					}
					$valueTypes = $this->create($exprNode->getArgs()[0]->value, $accessory, $newContext, false, $scope, $rootExpr);

					return $funcTypes->unionWith($valueTypes);
				}
			}

		}

		return null;
	}

	private function specifyTypesForConstantStringBinaryExpression(
		Expr $exprNode,
		ConstantStringType $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		?Expr $rootExpr,
	): ?SpecifiedTypes
	{
		if (
			$context->truthy()
			&& $exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& in_array(strtolower($exprNode->name->toString()), ['substr', 'strstr', 'stristr', 'strchr', 'strrchr', 'strtolower', 'strtoupper', 'mb_strtolower', 'mb_strtoupper', 'ucfirst', 'lcfirst', 'ucwords', 'mb_convert_case', 'mb_convert_kana'], true)
			&& isset($exprNode->getArgs()[0])
			&& $constantType->getValue() !== ''
		) {
			$argType = $scope->getType($exprNode->getArgs()[0]->value);

			if ($argType->isString()->yes()) {
				if ($constantType->getValue() !== '0') {
					return $this->create(
						$exprNode->getArgs()[0]->value,
						TypeCombinator::intersect($argType, new AccessoryNonFalsyStringType()),
						$context,
						false,
						$scope,
					);
				}

				return $this->create(
					$exprNode->getArgs()[0]->value,
					TypeCombinator::intersect($argType, new AccessoryNonEmptyStringType()),
					$context,
					false,
					$scope,
				);
			}
		}

		if (
			$exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& strtolower($exprNode->name->toString()) === 'gettype'
			&& isset($exprNode->getArgs()[0])
		) {
			$type = null;
			if ($constantType->getValue() === 'string') {
				$type = new StringType();
			}
			if ($constantType->getValue() === 'array') {
				$type = new ArrayType(new MixedType(), new MixedType());
			}
			if ($constantType->getValue() === 'boolean') {
				$type = new BooleanType();
			}
			if (in_array($constantType->getValue(), ['resource', 'resource (closed)'], true)) {
				$type = new ResourceType();
			}
			if ($constantType->getValue() === 'integer') {
				$type = new IntegerType();
			}
			if ($constantType->getValue() === 'double') {
				$type = new FloatType();
			}
			if ($constantType->getValue() === 'NULL') {
				$type = new NullType();
			}
			if ($constantType->getValue() === 'object') {
				$type = new ObjectWithoutClassType();
			}

			if ($type !== null) {
				$callType = $this->create($exprNode, $constantType, $context, false, $scope, $rootExpr);
				$argType = $this->create($exprNode->getArgs()[0]->value, $type, $context, false, $scope, $rootExpr);
				return $callType->unionWith($argType);
			}
		}

		if (
			$context->true()
			&& $exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& strtolower((string) $exprNode->name) === 'get_parent_class'
			&& isset($exprNode->getArgs()[0])
		) {
			$argType = $scope->getType($exprNode->getArgs()[0]->value);
			$objectType = new ObjectType($constantType->getValue());
			$classStringType = new GenericClassStringType($objectType);

			if ($argType->isString()->yes()) {
				return $this->create(
					$exprNode->getArgs()[0]->value,
					$classStringType,
					$context,
					false,
					$scope,
				);
			}

			if ($argType->isObject()->yes()) {
				return $this->create(
					$exprNode->getArgs()[0]->value,
					$objectType,
					$context,
					false,
					$scope,
				);
			}

			return $this->create(
				$exprNode->getArgs()[0]->value,
				TypeCombinator::union($objectType, $classStringType),
				$context,
				false,
				$scope,
			);
		}

		return null;
	}

	private function handleDefaultTruthyOrFalseyContext(TypeSpecifierContext $context, ?Expr $rootExpr, Expr $expr, Scope $scope): SpecifiedTypes
	{
		if ($context->null()) {
			return new SpecifiedTypes([], [], false, [], $rootExpr);
		}
		if (!$context->truthy()) {
			$type = StaticTypeFactory::truthy();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), false, $scope, $rootExpr);
		} elseif (!$context->falsey()) {
			$type = StaticTypeFactory::falsey();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), false, $scope, $rootExpr);
		}

		return new SpecifiedTypes([], [], false, [], $rootExpr);
	}

	private function specifyTypesFromConditionalReturnType(
		TypeSpecifierContext $context,
		Expr\CallLike $call,
		ParametersAcceptor $parametersAcceptor,
		Scope $scope,
	): ?SpecifiedTypes
	{
		if (!$parametersAcceptor instanceof ResolvedFunctionVariant) {
			return null;
		}

		$returnType = $parametersAcceptor->getOriginalParametersAcceptor()->getReturnType();
		if (!$returnType instanceof ConditionalTypeForParameter) {
			return null;
		}

		if ($context->true()) {
			$leftType = new ConstantBooleanType(true);
			$rightType = new ConstantBooleanType(false);
		} elseif ($context->false()) {
			$leftType = new ConstantBooleanType(false);
			$rightType = new ConstantBooleanType(true);
		} elseif ($context->null()) {
			$leftType = new MixedType();
			$rightType = new NeverType();
		} else {
			return null;
		}

		$argsMap = [];
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				continue;
			}

			if ($arg->name !== null) {
				$paramName = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$paramName = $parameters[$i]->getName();
			} else {
				continue;
			}

			$argsMap['$' . $paramName] = $arg->value;
		}

		return $this->getConditionalSpecifiedTypes($returnType, $leftType, $rightType, $scope, $argsMap);
	}

	/**
	 * @param array<string, Expr> $argsMap
	 */
	public function getConditionalSpecifiedTypes(
		ConditionalTypeForParameter $conditionalType,
		Type $leftType,
		Type $rightType,
		Scope $scope,
		array $argsMap,
	): ?SpecifiedTypes
	{
		$parameterName = $conditionalType->getParameterName();
		if (!array_key_exists($parameterName, $argsMap)) {
			return null;
		}

		$targetType = $conditionalType->getTarget();
		$ifType = $conditionalType->getIf();
		$elseType = $conditionalType->getElse();

		if ($leftType->isSuperTypeOf($ifType)->yes() && $rightType->isSuperTypeOf($elseType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue();
		} elseif ($leftType->isSuperTypeOf($elseType)->yes() && $rightType->isSuperTypeOf($ifType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
		} else {
			return null;
		}

		$specifiedTypes = $this->create(
			$argsMap[$parameterName],
			$targetType,
			$context,
			false,
			$scope,
		);

		if ($targetType instanceof ConstantBooleanType) {
			if (!$targetType->getValue()) {
				$context = $context->negate();
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->specifyTypesInCondition($scope, $argsMap[$parameterName], $context));
		}

		return $specifiedTypes;
	}

	private function specifyTypesFromAsserts(TypeSpecifierContext $context, Expr\CallLike $call, Assertions $assertions, ParametersAcceptor $parametersAcceptor, Scope $scope): ?SpecifiedTypes
	{
		if ($context->null()) {
			$asserts = $assertions->getAsserts();
		} elseif ($context->true()) {
			$asserts = $assertions->getAssertsIfTrue();
		} elseif ($context->false()) {
			$asserts = $assertions->getAssertsIfFalse();
		} else {
			throw new ShouldNotHappenException();
		}

		if (count($asserts) === 0) {
			return null;
		}

		$argsMap = [];
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				continue;
			}

			if ($arg->name !== null) {
				$paramName = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$paramName = $parameters[$i]->getName();
			} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
				$lastParameter = $parameters[count($parameters) - 1];
				$paramName = $lastParameter->getName();
			} else {
				continue;
			}

			$argsMap[$paramName][] = $arg->value;
		}

		if ($call instanceof MethodCall) {
			$argsMap['this'] = [$call->var];
		}

		/** @var SpecifiedTypes|null $types */
		$types = null;

		foreach ($asserts as $assert) {
			foreach ($argsMap[substr($assert->getParameter()->getParameterName(), 1)] ?? [] as $parameterExpr) {
				$assertedType = TypeTraverser::map($assert->getType(), static function (Type $type, callable $traverse) use ($argsMap, $scope): Type {
					if ($type instanceof ConditionalTypeForParameter) {
						$parameterName = substr($type->getParameterName(), 1);
						if (array_key_exists($parameterName, $argsMap)) {
							$argType = TypeCombinator::union(...array_map(static fn (Expr $expr) => $scope->getType($expr), $argsMap[$parameterName]));
							$type = $type->toConditional($argType);
						}
					}

					return $traverse($type);
				});

				$assertExpr = $assert->getParameter()->getExpr($parameterExpr);

				$templateTypeMap = $parametersAcceptor->getResolvedTemplateTypeMap();
				$containsUnresolvedTemplate = false;
				TypeTraverser::map(
					$assert->getOriginalType(),
					static function (Type $type, callable $traverse) use ($templateTypeMap, &$containsUnresolvedTemplate) {
						if ($type instanceof TemplateType && $type->getScope()->getClassName() !== null) {
							$resolvedType = $templateTypeMap->getType($type->getName());
							if ($resolvedType === null || $type->getBound()->equals($resolvedType)) {
								$containsUnresolvedTemplate = true;
								return $type;
							}
						}

						return $traverse($type);
					},
				);

				$newTypes = $this->create(
					$assertExpr,
					$assertedType,
					$assert->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue(),
					false,
					$scope,
					$containsUnresolvedTemplate || $assert->isEquality() ? $call : null,
				);
				$types = $types !== null ? $types->unionWith($newTypes) : $newTypes;

				if (!$context->null() || !$assertedType instanceof ConstantBooleanType) {
					continue;
				}

				$subContext = $assertedType->getValue() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
				if ($assert->isNegated()) {
					$subContext = $subContext->negate();
				}

				$types = $types->unionWith($this->specifyTypesInCondition(
					$scope,
					$assertExpr,
					$subContext,
				));
			}
		}

		return $types;
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processBooleanConditionalTypes(Scope $scope, SpecifiedTypes $leftTypes, SpecifiedTypes $rightTypes): array
	{
		$conditionExpressionTypes = [];
		foreach ($leftTypes->getSureNotTypes() as $exprString => [$expr, $type]) {
			if (!$expr instanceof Expr\Variable) {
				continue;
			}
			if (!is_string($expr->name)) {
				continue;
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes(
				$expr,
				TypeCombinator::intersect($scope->getType($expr), $type),
			);
		}

		if (count($conditionExpressionTypes) > 0) {
			$holders = [];
			foreach ($rightTypes->getSureNotTypes() as $exprString => [$expr, $type]) {
				if (!$expr instanceof Expr\Variable) {
					continue;
				}
				if (!is_string($expr->name)) {
					continue;
				}

				if (!isset($holders[$exprString])) {
					$holders[$exprString] = [];
				}

				$holder = new ConditionalExpressionHolder(
					$conditionExpressionTypes,
					new ExpressionTypeHolder($expr, TypeCombinator::remove($scope->getType($expr), $type), TrinaryLogic::createYes()),
				);
				$holders[$exprString][$holder->getKey()] = $holder;
			}

			return $holders;
		}

		return [];
	}

	/**
	 * @return array{Expr, ConstantScalarType}|null
	 */
	private function findTypeExpressionsFromBinaryOperation(Scope $scope, Node\Expr\BinaryOp $binaryOperation): ?array
	{
		$leftType = $scope->getType($binaryOperation->left);
		$rightType = $scope->getType($binaryOperation->right);

		$rightExpr = $binaryOperation->right;
		if ($rightExpr instanceof AlwaysRememberedExpr) {
			$rightExpr = $rightExpr->getExpr();
		}

		$leftExpr = $binaryOperation->left;
		if ($leftExpr instanceof AlwaysRememberedExpr) {
			$leftExpr = $leftExpr->getExpr();
		}

		if (
			$leftType instanceof ConstantScalarType
			&& !$rightExpr instanceof ConstFetch
			&& !$rightExpr instanceof ClassConstFetch
		) {
			return [$binaryOperation->right, $leftType];
		} elseif (
			$rightType instanceof ConstantScalarType
			&& !$leftExpr instanceof ConstFetch
			&& !$leftExpr instanceof ClassConstFetch
		) {
			return [$binaryOperation->left, $rightType];
		}

		return null;
	}

	/** @api */
	public function create(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		bool $overwrite = false,
		?Scope $scope = null,
		?Expr $rootExpr = null,
	): SpecifiedTypes
	{
		if ($expr instanceof Instanceof_ || $expr instanceof Expr\List_) {
			return new SpecifiedTypes([], [], false, [], $rootExpr);
		}

		$specifiedExprs = [];

		if ($expr instanceof Expr\Assign) {
			$specifiedExprs[] = $expr->var;

			while ($expr->expr instanceof Expr\Assign) {
				$specifiedExprs[] = $expr->expr->var;
				$expr = $expr->expr;
			}
		} elseif ($expr instanceof Expr\AssignOp\Coalesce) {
			$specifiedExprs[] = $expr->var;
		} else {
			$specifiedExprs[] = $expr;
		}

		$types = null;

		foreach ($specifiedExprs as $specifiedExpr) {
			$newTypes = $this->createForExpr($specifiedExpr, $type, $context, $overwrite, $scope, $rootExpr);

			if ($types === null) {
				$types = $newTypes;
			} else {
				$types = $types->unionWith($newTypes);
			}
		}

		return $types;
	}

	private function createForExpr(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		bool $overwrite = false,
		?Scope $scope = null,
		?Expr $rootExpr = null,
	): SpecifiedTypes
	{
		if ($scope !== null) {
			if ($context->true()) {
				$containsNull = !$type->isNull()->no() && !$scope->getType($expr)->isNull()->no();
			} elseif ($context->false()) {
				$containsNull = !TypeCombinator::containsNull($type) && !$scope->getType($expr)->isNull()->no();
			}
		}

		$originalExpr = $expr;
		if (isset($containsNull) && !$containsNull) {
			$expr = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr);
		}

		if (
			$scope !== null
			&& !$context->null()
			&& $expr instanceof Expr\BinaryOp\Coalesce
		) {
			$rightIsSuperType = $type->isSuperTypeOf($scope->getType($expr->right));
			if (($context->true() && $rightIsSuperType->no()) || ($context->false() && $rightIsSuperType->yes())) {
				$expr = $expr->left;
			}
		}

		if (
			$expr instanceof FuncCall
			&& $expr->name instanceof Name
		) {
			$has = $this->reflectionProvider->hasFunction($expr->name, $scope);
			if (!$has) {
				// backwards compatibility with previous behaviour
				return new SpecifiedTypes([], [], false, [], $rootExpr);
			}

			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$hasSideEffects = $functionReflection->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return new SpecifiedTypes([], [], false, [], $rootExpr);
			}

			if (!$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no()) {
				return new SpecifiedTypes([], [], false, [], $rootExpr);
			}
		}

		if (
			$expr instanceof MethodCall
			&& $expr->name instanceof Node\Identifier
			&& $scope !== null
		) {
			$methodName = $expr->name->toString();
			$calledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				if (isset($containsNull) && !$containsNull) {
					return $this->createNullsafeTypes($rootExpr, $originalExpr, $scope, $context, $overwrite, $type);
				}

				return new SpecifiedTypes([], [], false, [], $rootExpr);
			}
		}

		if (
			$expr instanceof StaticCall
			&& $expr->name instanceof Node\Identifier
			&& $scope !== null
		) {
			$methodName = $expr->name->toString();
			if ($expr->class instanceof Name) {
				$calledOnType = $scope->resolveTypeByName($expr->class);
			} else {
				$calledOnType = $scope->getType($expr->class);
			}

			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				if (isset($containsNull) && !$containsNull) {
					return $this->createNullsafeTypes($rootExpr, $originalExpr, $scope, $context, $overwrite, $type);
				}

				return new SpecifiedTypes([], [], false, [], $rootExpr);
			}
		}

		$sureTypes = [];
		$sureNotTypes = [];
		$exprString = $this->exprPrinter->printExpr($expr);
		$originalExprString = $this->exprPrinter->printExpr($originalExpr);
		if ($context->false()) {
			$sureNotTypes[$exprString] = [$expr, $type];
			if ($exprString !== $originalExprString) {
				$sureNotTypes[$originalExprString] = [$originalExpr, $type];
			}
		} elseif ($context->true()) {
			$sureTypes[$exprString] = [$expr, $type];
			if ($exprString !== $originalExprString) {
				$sureTypes[$originalExprString] = [$originalExpr, $type];
			}
		}

		$types = new SpecifiedTypes($sureTypes, $sureNotTypes, $overwrite, [], $rootExpr);
		if ($scope !== null && isset($containsNull) && !$containsNull) {
			return $this->createNullsafeTypes($rootExpr, $originalExpr, $scope, $context, $overwrite, $type)->unionWith($types);
		}

		return $types;
	}

	private function createNullsafeTypes(?Expr $rootExpr, Expr $expr, Scope $scope, TypeSpecifierContext $context, bool $overwrite, ?Type $type): SpecifiedTypes
	{
		if ($expr instanceof Expr\NullsafePropertyFetch) {
			if ($type !== null) {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), $type, $context, false, $scope, $rootExpr);
			} else {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), new NullType(), TypeSpecifierContext::createFalse(), false, $scope, $rootExpr);
			}

			return $propertyFetchTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $overwrite, $scope, $rootExpr),
			);
		}

		if ($expr instanceof Expr\NullsafeMethodCall) {
			if ($type !== null) {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), $type, $context, $overwrite, $scope, $rootExpr);
			} else {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), new NullType(), TypeSpecifierContext::createFalse(), $overwrite, $scope, $rootExpr);
			}

			return $methodCallTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $overwrite, $scope, $rootExpr),
			);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->createNullsafeTypes($rootExpr, $expr->var, $scope, $context, $overwrite, null);
		}

		if ($expr instanceof Expr\MethodCall) {
			return $this->createNullsafeTypes($rootExpr, $expr->var, $scope, $context, $overwrite, null);
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->createNullsafeTypes($rootExpr, $expr->var, $scope, $context, $overwrite, null);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($rootExpr, $expr->class, $scope, $context, $overwrite, null);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($rootExpr, $expr->class, $scope, $context, $overwrite, null);
		}

		return new SpecifiedTypes([], [], $overwrite, [], $rootExpr);
	}

	private function createRangeTypes(?Expr $rootExpr, Expr $expr, Type $type, TypeSpecifierContext $context): SpecifiedTypes
	{
		$sureNotTypes = [];

		if ($type instanceof IntegerRangeType || $type instanceof ConstantIntegerType) {
			$exprString = $this->exprPrinter->printExpr($expr);
			if ($context->false()) {
				$sureNotTypes[$exprString] = [$expr, $type];
			} elseif ($context->true()) {
				$inverted = TypeCombinator::remove(new IntegerType(), $type);
				$sureNotTypes[$exprString] = [$expr, $inverted];
			}
		}

		return new SpecifiedTypes([], $sureNotTypes, false, [], $rootExpr);
	}

	/**
	 * @return FunctionTypeSpecifyingExtension[]
	 */
	private function getFunctionTypeSpecifyingExtensions(): array
	{
		return $this->functionTypeSpecifyingExtensions;
	}

	/**
	 * @return MethodTypeSpecifyingExtension[]
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
	 * @return StaticMethodTypeSpecifyingExtension[]
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
	 * @param MethodTypeSpecifyingExtension[][]|StaticMethodTypeSpecifyingExtension[][] $extensions
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

	public function resolveIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context, Expr $rootExpr): SpecifiedTypes
	{
		$leftExpr = $expr->left;
		$rightExpr = $expr->right;
		if ($rightExpr instanceof FuncCall && !$leftExpr instanceof FuncCall) {
			[$leftExpr, $rightExpr] = [$rightExpr, $leftExpr];
		}
		$unwrappedLeftExpr = $leftExpr;
		if ($leftExpr instanceof AlwaysRememberedExpr) {
			$unwrappedLeftExpr = $leftExpr->getExpr();
		}
		$unwrappedRightExpr = $rightExpr;
		if ($rightExpr instanceof AlwaysRememberedExpr) {
			$unwrappedRightExpr = $rightExpr->getExpr();
		}
		$rightType = $scope->getType($rightExpr);
		if (
			$context->true()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& strtolower($unwrappedLeftExpr->name->toString()) === 'get_class'
			&& isset($unwrappedLeftExpr->getArgs()[0])
		) {
			if ($rightType->getClassStringObjectType()->isObject()->yes()) {
				return $this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					$rightType->getClassStringObjectType(),
					$context,
					false,
					$scope,
					$rootExpr,
				)->unionWith($this->create($leftExpr, $rightType, $context, false, $scope, $rootExpr));
			}
		}

		if (count($rightType->getConstantStrings()) > 0) {
			$types = null;
			foreach ($rightType->getConstantStrings() as $constantString) {
				$specifiedType = $this->specifyTypesForConstantStringBinaryExpression($unwrappedLeftExpr, $constantString, $context, $scope, $rootExpr);
				if ($specifiedType === null) {
					continue;
				}
				if ($types === null) {
					$types = $specifiedType;
					continue;
				}

				$types = $types->intersectWith($specifiedType);
			}

			if ($types !== null) {
				if ($leftExpr !== $unwrappedLeftExpr) {
					$types = $types->unionWith($this->create($leftExpr, $rightType, $context, false, $scope, $rootExpr));
				}
				return $types;
			}
		}

		$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
		if ($expressions !== null) {
			$exprNode = $expressions[0];
			$constantType = $expressions[1];

			$specifiedType = $this->specifyTypesForConstantBinaryExpression($exprNode, $constantType, $context, $scope, $rootExpr);
			if ($specifiedType !== null) {
				if ($exprNode instanceof AlwaysRememberedExpr) {
					$specifiedType->unionWith(
						$this->create($exprNode->getExpr(), $constantType, $context, false, $scope, $rootExpr),
					);
				}
				return $specifiedType;
			}
		}

		if (
			$context->true() &&
			$unwrappedLeftExpr instanceof ClassConstFetch &&
			$unwrappedLeftExpr->class instanceof Expr &&
			$unwrappedLeftExpr->name instanceof Node\Identifier &&
			$unwrappedRightExpr instanceof ClassConstFetch &&
			$rightType instanceof ConstantStringType &&
			strtolower($unwrappedLeftExpr->name->toString()) === 'class'
		) {
			return $this->specifyTypesInCondition(
				$scope,
				new Instanceof_(
					$unwrappedLeftExpr->class,
					new Name($rightType->getValue()),
				),
				$context,
				$rootExpr,
			)->unionWith($this->create($leftExpr, $rightType, $context, false, $scope, $rootExpr));
		}

		$leftType = $scope->getType($leftExpr);
		if (
			$context->true() &&
			$unwrappedRightExpr instanceof ClassConstFetch &&
			$unwrappedRightExpr->class instanceof Expr &&
			$unwrappedRightExpr->name instanceof Node\Identifier &&
			$unwrappedLeftExpr instanceof ClassConstFetch &&
			$leftType instanceof ConstantStringType &&
			strtolower($unwrappedRightExpr->name->toString()) === 'class'
		) {
			return $this->specifyTypesInCondition(
				$scope,
				new Instanceof_(
					$unwrappedRightExpr->class,
					new Name($leftType->getValue()),
				),
				$context,
				$rootExpr,
			)->unionWith($this->create($rightExpr, $leftType, $context, false, $scope, $rootExpr));
		}

		if ($context->false()) {
			$identicalType = $scope->getType($expr);
			if ($identicalType instanceof ConstantBooleanType) {
				$never = new NeverType();
				$contextForTypes = $identicalType->getValue() ? $context->negate() : $context;
				$leftTypes = $this->create($leftExpr, $never, $contextForTypes, false, $scope, $rootExpr);
				$rightTypes = $this->create($rightExpr, $never, $contextForTypes, false, $scope, $rootExpr);
				if ($leftExpr instanceof AlwaysRememberedExpr) {
					$leftTypes = $leftTypes->unionWith(
						$this->create($unwrappedLeftExpr, $never, $contextForTypes, false, $scope, $rootExpr),
					);
				}
				if ($rightExpr instanceof AlwaysRememberedExpr) {
					$rightTypes = $rightTypes->unionWith(
						$this->create($unwrappedRightExpr, $never, $contextForTypes, false, $scope, $rootExpr),
					);
				}
				return $leftTypes->unionWith($rightTypes);
			}
		}

		$types = null;
		if (
			count($leftType->getFiniteTypes()) === 1
			|| ($context->true() && $leftType->isConstantValue()->yes() && !$rightType->equals($leftType) && $rightType->isSuperTypeOf($leftType)->yes())
		) {
			$types = $this->create(
				$rightExpr,
				$leftType,
				$context,
				false,
				$scope,
				$rootExpr,
			);
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$types = $types->unionWith($this->create(
					$unwrappedRightExpr,
					$leftType,
					$context,
					false,
					$scope,
					$rootExpr,
				));
			}
		}
		if (
			count($rightType->getFiniteTypes()) === 1
			|| ($context->true() && $rightType->isConstantValue()->yes() && !$leftType->equals($rightType) && $leftType->isSuperTypeOf($rightType)->yes())
		) {
			$leftTypes = $this->create(
				$leftExpr,
				$rightType,
				$context,
				false,
				$scope,
				$rootExpr,
			);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith($this->create(
					$unwrappedLeftExpr,
					$rightType,
					$context,
					false,
					$scope,
					$rootExpr,
				));
			}
			if ($types !== null) {
				$types = $types->unionWith($leftTypes);
			} else {
				$types = $leftTypes;
			}
		}

		if ($types !== null) {
			return $types;
		}

		$leftExprString = $this->exprPrinter->printExpr($unwrappedLeftExpr);
		$rightExprString = $this->exprPrinter->printExpr($unwrappedRightExpr);
		if ($leftExprString === $rightExprString) {
			if (!$unwrappedLeftExpr instanceof Expr\Variable || !$unwrappedRightExpr instanceof Expr\Variable) {
				return new SpecifiedTypes([], [], false, [], $rootExpr);
			}
		}

		if ($context->true()) {
			$leftTypes = $this->create($leftExpr, $rightType, $context, false, $scope, $rootExpr);
			$rightTypes = $this->create($rightExpr, $leftType, $context, false, $scope, $rootExpr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith(
					$this->create($unwrappedLeftExpr, $rightType, $context, false, $scope, $rootExpr),
				);
			}
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$rightTypes = $rightTypes->unionWith(
					$this->create($unwrappedRightExpr, $leftType, $context, false, $scope, $rootExpr),
				);
			}
			return $leftTypes->unionWith($rightTypes);
		} elseif ($context->false()) {
			return $this->create($leftExpr, $leftType, $context, false, $scope, $rootExpr)->normalize($scope)
				->intersectWith($this->create($rightExpr, $rightType, $context, false, $scope, $rootExpr)->normalize($scope));
		}

		return new SpecifiedTypes([], [], false, [], $rootExpr);
	}

}
