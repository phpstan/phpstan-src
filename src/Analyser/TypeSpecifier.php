<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Countable;
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
use PHPStan\Analyser\ExprHandler\BooleanAndHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Rules\Arrays\AllowedArrayKeysTypes;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
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
use function array_key_first;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_shift;
use function count;
use function in_array;
use function is_string;
use function strtolower;
use function substr;
use const COUNT_NORMAL;

#[AutowiredService(name: 'typeSpecifier', factory: '@typeSpecifierFactory::create')]
final class TypeSpecifier
{

	private const BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4;

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
		private PhpVersion $phpVersion,
		private array $functionTypeSpecifyingExtensions,
		private array $methodTypeSpecifyingExtensions,
		private array $staticMethodTypeSpecifyingExtensions,
		private bool $rememberPossiblyImpureFunctionValues,
	)
	{
	}

	/**
	 * @api
	 */
	public function specifyTypesInCondition(
		Scope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		if ($expr instanceof Expr\CallLike && $expr->isFirstClassCallable()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
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
				return $this->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
			}

			$result = $scope->getType($expr->class)->toObjectTypeForInstanceofCheck();
			$type = $result->type;
			$uncertainty = $result->uncertainty;

			if (!$type->isSuperTypeOf(new MixedType())->yes()) {
				if ($context->true()) {
					$type = TypeCombinator::intersect(
						$type,
						new ObjectWithoutClassType(),
					);
					return $this->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
				} elseif ($context->false() && !$uncertainty) {
					$exprType = $scope->getType($expr->expr);
					if (!$type->isSuperTypeOf($exprType)->yes()) {
						return $this->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
					}
				}
			}
			if ($context->true()) {
				return $this->create($exprNode, new ObjectWithoutClassType(), $context, $scope)->setRootExpr($exprNode);
			}
		} elseif ($expr instanceof Node\Expr\BinaryOp\Identical) {
			return $this->resolveIdentical($expr, $scope, $context);

		} elseif ($expr instanceof Node\Expr\BinaryOp\NotIdentical) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BooleanNot(new Node\Expr\BinaryOp\Identical($expr->left, $expr->right)),
				$context,
			)->setRootExpr($expr);
		} elseif ($expr instanceof Expr\Cast\Bool_) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BinaryOp\Equal($expr->expr, new ConstFetch(new Name\FullyQualified('true'))),
				$context,
			)->setRootExpr($expr);
		} elseif ($expr instanceof Expr\Cast\String_) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BinaryOp\NotEqual($expr->expr, new Node\Scalar\String_('')),
				$context,
			)->setRootExpr($expr);
		} elseif ($expr instanceof Expr\Cast\Int_) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BinaryOp\NotEqual($expr->expr, new Node\Scalar\Int_(0)),
				$context,
			)->setRootExpr($expr);
		} elseif ($expr instanceof Expr\Cast\Double) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BinaryOp\NotEqual($expr->expr, new Node\Scalar\Float_(0.0)),
				$context,
			)->setRootExpr($expr);
		} elseif ($expr instanceof Node\Expr\BinaryOp\Equal) {
			return $this->resolveEqual($expr, $scope, $context);
		} elseif ($expr instanceof Node\Expr\BinaryOp\NotEqual) {
			return $this->specifyTypesInCondition(
				$scope,
				new Node\Expr\BooleanNot(new Node\Expr\BinaryOp\Equal($expr->left, $expr->right)),
				$context,
			)->setRootExpr($expr);

		} elseif ($expr instanceof Node\Expr\BinaryOp\Smaller || $expr instanceof Node\Expr\BinaryOp\SmallerOrEqual) {

			if (
				$expr->left instanceof FuncCall
				&& $expr->left->name instanceof Name
				&& !$expr->left->isFirstClassCallable()
				&& in_array(strtolower((string) $expr->left->name), ['count', 'sizeof', 'strlen', 'mb_strlen', 'preg_match'], true)
				&& count($expr->left->getArgs()) >= 1
				&& (
					!$expr->right instanceof FuncCall
					|| !$expr->right->name instanceof Name
					|| !in_array(strtolower((string) $expr->right->name), ['count', 'sizeof', 'strlen', 'mb_strlen', 'preg_match'], true)
				)
			) {
				$inverseOperator = $expr instanceof Node\Expr\BinaryOp\Smaller
					? new Node\Expr\BinaryOp\SmallerOrEqual($expr->right, $expr->left)
					: new Node\Expr\BinaryOp\Smaller($expr->right, $expr->left);

				return $this->specifyTypesInCondition(
					$scope,
					new Node\Expr\BooleanNot($inverseOperator),
					$context,
				)->setRootExpr($expr);
			}

			$orEqual = $expr instanceof Node\Expr\BinaryOp\SmallerOrEqual;
			$offset = $orEqual ? 0 : 1;
			$leftType = $scope->getType($expr->left);
			$result = (new SpecifiedTypes([], []))->setRootExpr($expr);

			if (
				!$context->null()
				&& $expr->right instanceof FuncCall
				&& $expr->right->name instanceof Name
				&& !$expr->right->isFirstClassCallable()
				&& in_array(strtolower((string) $expr->right->name), ['count', 'sizeof'], true)
				&& count($expr->right->getArgs()) >= 1
				&& $leftType->isInteger()->yes()
			) {
				$argType = $scope->getType($expr->right->getArgs()[0]->value);

				$sizeType = null;
				if ($leftType instanceof ConstantIntegerType) {
					if ($orEqual) {
						$sizeType = IntegerRangeType::createAllGreaterThanOrEqualTo($leftType->getValue());
					} else {
						$sizeType = IntegerRangeType::createAllGreaterThan($leftType->getValue());
					}
				} elseif ($leftType instanceof IntegerRangeType) {
					if ($context->falsey() && $leftType->getMax() !== null) {
						if ($orEqual) {
							$sizeType = IntegerRangeType::createAllGreaterThanOrEqualTo($leftType->getMax());
						} else {
							$sizeType = IntegerRangeType::createAllGreaterThan($leftType->getMax());
						}
					} elseif ($context->truthy() && $leftType->getMin() !== null) {
						if ($orEqual) {
							$sizeType = IntegerRangeType::createAllGreaterThanOrEqualTo($leftType->getMin());
						} else {
							$sizeType = IntegerRangeType::createAllGreaterThan($leftType->getMin());
						}
					}
				} else {
					$sizeType = $leftType;
				}

				if ($sizeType !== null) {
					$specifiedTypes = $this->specifyTypesForCountFuncCall($expr->right, $argType, $sizeType, $context, $scope, $expr);
					if ($specifiedTypes !== null) {
						$result = $result->unionWith($specifiedTypes);
					}
				}

				if (
					$context->true() && (IntegerRangeType::createAllGreaterThanOrEqualTo(1 - $offset)->isSuperTypeOf($leftType)->yes())
					|| ($context->false() && (new ConstantIntegerType(1 - $offset))->isSuperTypeOf($leftType)->yes())
				) {
					if ($context->truthy() && $argType->isArray()->maybe()) {
						$countables = [];
						if ($argType instanceof UnionType) {
							$countableInterface = new ObjectType(Countable::class);
							foreach ($argType->getTypes() as $innerType) {
								if ($innerType->isArray()->yes()) {
									$innerType = TypeCombinator::intersect(new NonEmptyArrayType(), $innerType);
									$countables[] = $innerType;
								}

								if (!$countableInterface->isSuperTypeOf($innerType)->yes()) {
									continue;
								}

								$countables[] = $innerType;
							}
						}

						if (count($countables) > 0) {
							$countableType = TypeCombinator::union(...$countables);

							return $this->create($expr->right->getArgs()[0]->value, $countableType, $context, $scope)->setRootExpr($expr);
						}
					}

					if ($argType->isArray()->yes()) {
						$newType = new NonEmptyArrayType();
						if ($context->true() && $argType->isList()->yes()) {
							$newType = TypeCombinator::intersect($newType, new AccessoryArrayListType());
						}

						$result = $result->unionWith(
							$this->create($expr->right->getArgs()[0]->value, $newType, $context, $scope)->setRootExpr($expr),
						);
					}
				}

				// infer $list[$index] after $index < count($list)
				if (
					$context->true()
					&& !$orEqual
					// constant offsets are handled via HasOffsetType/HasOffsetValueType
					&& !$leftType instanceof ConstantIntegerType
					&& $argType->isList()->yes()
					&& IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($leftType)->yes()
				) {
					$arrayArg = $expr->right->getArgs()[0]->value;
					$dimFetch = new ArrayDimFetch($arrayArg, $expr->left);
					$result = $result->unionWith(
						$this->create($dimFetch, $argType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope)->setRootExpr($expr),
					);
				}
			}

			// infer $list[$index] after $zeroOrMore < count($list) - N
			// infer $list[$index] after $zeroOrMore <= count($list) - N
			if (
				$context->true()
				&& $expr->right instanceof Expr\BinaryOp\Minus
				&& $expr->right->left instanceof FuncCall
				&& $expr->right->left->name instanceof Name
				&& !$expr->right->left->isFirstClassCallable()
				&& in_array(strtolower((string) $expr->right->left->name), ['count', 'sizeof'], true)
				&& count($expr->right->left->getArgs()) >= 1
				// constant offsets are handled via HasOffsetType/HasOffsetValueType
				&& !$leftType instanceof ConstantIntegerType
				&& $leftType->isInteger()->yes()
				&& IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($leftType)->yes()
			) {
				$countArgType = $scope->getType($expr->right->left->getArgs()[0]->value);
				$subtractedType = $scope->getType($expr->right->right);
				if (
					$countArgType->isList()->yes()
					&& $this->isNormalCountCall($expr->right->left, $countArgType, $scope)->yes()
					&& IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($subtractedType)->yes()
				) {
					$arrayArg = $expr->right->left->getArgs()[0]->value;
					$dimFetch = new ArrayDimFetch($arrayArg, $expr->left);
					$result = $result->unionWith(
						$this->create($dimFetch, $countArgType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope)->setRootExpr($expr),
					);
				}
			}

			if (
				!$context->null()
				&& $expr->right instanceof FuncCall
				&& $expr->right->name instanceof Name
				&& !$expr->right->isFirstClassCallable()
				&& in_array(strtolower((string) $expr->right->name), ['preg_match'], true)
				&& count($expr->right->getArgs()) >= 3
				&& (
					IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($leftType)->yes()
					|| ($expr instanceof Expr\BinaryOp\Smaller && IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($leftType)->yes())
				)
			) {
				// 0 < preg_match or 1 <= preg_match becomes 1 === preg_match
				$newExpr = new Expr\BinaryOp\Identical($expr->right, new Node\Scalar\Int_(1));

				return $this->specifyTypesInCondition($scope, $newExpr, $context)->setRootExpr($expr);
			}

			if (
				!$context->null()
				&& $expr->right instanceof FuncCall
				&& $expr->right->name instanceof Name
				&& !$expr->right->isFirstClassCallable()
				&& in_array(strtolower((string) $expr->right->name), ['strlen', 'mb_strlen'], true)
				&& count($expr->right->getArgs()) === 1
				&& $leftType->isInteger()->yes()
			) {
				if (
					$context->true() && (IntegerRangeType::createAllGreaterThanOrEqualTo(1 - $offset)->isSuperTypeOf($leftType)->yes())
					|| ($context->false() && (new ConstantIntegerType(1 - $offset))->isSuperTypeOf($leftType)->yes())
				) {
					$argType = $scope->getType($expr->right->getArgs()[0]->value);
					if ($argType->isString()->yes()) {
						$accessory = new AccessoryNonEmptyStringType();

						if (IntegerRangeType::createAllGreaterThanOrEqualTo(2 - $offset)->isSuperTypeOf($leftType)->yes()) {
							$accessory = new AccessoryNonFalsyStringType();
						}

						$result = $result->unionWith($this->create($expr->right->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($expr));
					}
				}
			}

			if ($leftType instanceof ConstantIntegerType) {
				if ($expr->right instanceof Expr\PostInc) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr,
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset + 1),
						$context,
					));
				} elseif ($expr->right instanceof Expr\PostDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr,
						$expr->right->var,
						IntegerRangeType::fromInterval($leftType->getValue(), null, $offset - 1),
						$context,
					));
				} elseif ($expr->right instanceof Expr\PreInc || $expr->right instanceof Expr\PreDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr,
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
						$expr,
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset + 1),
						$context,
					));
				} elseif ($expr->left instanceof Expr\PostDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr,
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset - 1),
						$context,
					));
				} elseif ($expr->left instanceof Expr\PreInc || $expr->left instanceof Expr\PreDec) {
					$result = $result->unionWith($this->createRangeTypes(
						$expr,
						$expr->left->var,
						IntegerRangeType::fromInterval(null, $rightType->getValue(), -$offset),
						$context,
					));
				}
			}

			if ($context->true()) {
				if (!$expr->left instanceof Node\Scalar && !($expr->left instanceof Expr\UnaryMinus && $expr->left->expr instanceof Node\Scalar)) {
					$result = $result->unionWith(
						$this->create(
							$expr->left,
							$orEqual ? $rightType->getSmallerOrEqualType($this->phpVersion) : $rightType->getSmallerType($this->phpVersion),
							TypeSpecifierContext::createTruthy(),
							$scope,
						)->setRootExpr($expr),
					);
				}
				if (!$expr->right instanceof Node\Scalar && !($expr->right instanceof Expr\UnaryMinus && $expr->right->expr instanceof Node\Scalar)) {
					$result = $result->unionWith(
						$this->create(
							$expr->right,
							$orEqual ? $leftType->getGreaterOrEqualType($this->phpVersion) : $leftType->getGreaterType($this->phpVersion),
							TypeSpecifierContext::createTruthy(),
							$scope,
						)->setRootExpr($expr),
					);
				}
			} elseif ($context->false()) {
				if (!$expr->left instanceof Node\Scalar && !($expr->left instanceof Expr\UnaryMinus && $expr->left->expr instanceof Node\Scalar)) {
					$result = $result->unionWith(
						$this->create(
							$expr->left,
							$orEqual ? $rightType->getGreaterType($this->phpVersion) : $rightType->getGreaterOrEqualType($this->phpVersion),
							TypeSpecifierContext::createTruthy(),
							$scope,
						)->setRootExpr($expr),
					);
				}
				if (!$expr->right instanceof Node\Scalar && !($expr->right instanceof Expr\UnaryMinus && $expr->right->expr instanceof Node\Scalar)) {
					$result = $result->unionWith(
						$this->create(
							$expr->right,
							$orEqual ? $leftType->getSmallerType($this->phpVersion) : $leftType->getSmallerOrEqualType($this->phpVersion),
							TypeSpecifierContext::createTruthy(),
							$scope,
						)->setRootExpr($expr),
					);
				}
			}

			return $result;

		} elseif ($expr instanceof Node\Expr\BinaryOp\Greater) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Smaller($expr->right, $expr->left), $context)->setRootExpr($expr);

		} elseif ($expr instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\SmallerOrEqual($expr->right, $expr->left), $context)->setRootExpr($expr);

		} elseif ($expr instanceof FuncCall && $expr->name instanceof Name) {
			if ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				// lazy create parametersAcceptor, as creation can be expensive
				$parametersAcceptor = null;

				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				$normalizedExpr = $expr;
				$args = $expr->getArgs();
				if (count($args) > 0) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants());
					$normalizedExpr = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr) ?? $expr;
				}

				foreach ($this->getFunctionTypeSpecifyingExtensions() as $extension) {
					if (!$extension->isFunctionSupported($functionReflection, $normalizedExpr, $context)) {
						continue;
					}

					return $extension->specifyTypes($functionReflection, $normalizedExpr, $scope, $context);
				}

				if (count($args) > 0) {
					$specifiedTypes = $this->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$assertions = $functionReflection->getAsserts();
				if ($assertions->getAll() !== []) {
					$parametersAcceptor ??= ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants());

					$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
						$type,
						$parametersAcceptor->getResolvedTemplateTypeMap(),
						$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						TemplateTypeVariance::createInvariant(),
					));
					$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		} elseif ($expr instanceof FuncCall) {
			$specifiedTypes = $this->specifyTypesFromCallableCall($context, $expr, $scope);
			if ($specifiedTypes !== null) {
				return $specifiedTypes;
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		} elseif ($expr instanceof MethodCall && $expr->name instanceof Node\Identifier) {
			$methodCalledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($methodCalledOnType, $expr->name->name);
			if ($methodReflection !== null) {
				// lazy create parametersAcceptor, as creation can be expensive
				$parametersAcceptor = null;

				$normalizedExpr = $expr;
				$args = $expr->getArgs();
				if (count($args) > 0) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants());
					$normalizedExpr = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $expr) ?? $expr;
				}

				$referencedClasses = $methodCalledOnType->getObjectClassNames();
				if (
					count($referencedClasses) === 1
					&& $this->reflectionProvider->hasClass($referencedClasses[0])
				) {
					$methodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
					foreach ($this->getMethodTypeSpecifyingExtensionsForClass($methodClassReflection->getName()) as $extension) {
						if (!$extension->isMethodSupported($methodReflection, $normalizedExpr, $context)) {
							continue;
						}

						return $extension->specifyTypes($methodReflection, $normalizedExpr, $scope, $context);
					}
				}

				if (count($args) > 0) {
					$specifiedTypes = $this->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$assertions = $methodReflection->getAsserts();
				if ($assertions->getAll() !== []) {
					$parametersAcceptor ??= ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants());

					$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
						$type,
						$parametersAcceptor->getResolvedTemplateTypeMap(),
						$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						TemplateTypeVariance::createInvariant(),
					));
					$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		} elseif ($expr instanceof StaticCall && $expr->name instanceof Node\Identifier) {
			if ($expr->class instanceof Name) {
				$calleeType = $scope->resolveTypeByName($expr->class);
			} else {
				$calleeType = $scope->getType($expr->class);
			}

			$staticMethodReflection = $scope->getMethodReflection($calleeType, $expr->name->name);
			if ($staticMethodReflection !== null) {
				// lazy create parametersAcceptor, as creation can be expensive
				$parametersAcceptor = null;

				$normalizedExpr = $expr;
				$args = $expr->getArgs();
				if (count($args) > 0) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $staticMethodReflection->getVariants(), $staticMethodReflection->getNamedArgumentsVariants());
					$normalizedExpr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
				}

				$referencedClasses = $calleeType->getObjectClassNames();
				if (
					count($referencedClasses) === 1
					&& $this->reflectionProvider->hasClass($referencedClasses[0])
				) {
					$staticMethodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
					foreach ($this->getStaticMethodTypeSpecifyingExtensionsForClass($staticMethodClassReflection->getName()) as $extension) {
						if (!$extension->isStaticMethodSupported($staticMethodReflection, $normalizedExpr, $context)) {
							continue;
						}

						return $extension->specifyTypes($staticMethodReflection, $normalizedExpr, $scope, $context);
					}
				}

				if (count($args) > 0) {
					$specifiedTypes = $this->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$assertions = $staticMethodReflection->getAsserts();
				if ($assertions->getAll() !== []) {
					$parametersAcceptor ??= ParametersAcceptorSelector::selectFromArgs($scope, $args, $staticMethodReflection->getVariants(), $staticMethodReflection->getNamedArgumentsVariants());

					$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
						$type,
						$parametersAcceptor->getResolvedTemplateTypeMap(),
						$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						TemplateTypeVariance::createInvariant(),
					));
					$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}
			}

			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		} elseif ($expr instanceof BooleanAnd || $expr instanceof LogicalAnd) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}

			// For deep BooleanAnd chains in truthy context, flatten and
			// process all arms at once to avoid O(N²) recursive
			// filterByTruthyValue calls.
			if (
				$context->true()
				&& BooleanAndHandler::getBooleanExpressionDepth($expr) > self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH
			) {
				return $this->specifyTypesForFlattenedBooleanAnd($scope, $expr, $context);
			}

			$leftTypes = $this->specifyTypesInCondition($scope, $expr->left, $context)->setRootExpr($expr);
			$rightScope = $scope->filterByTruthyValue($expr->left);
			$rightTypes = $this->specifyTypesInCondition($rightScope, $expr->right, $context)->setRootExpr($expr);
			if ($context->true()) {
				$types = $leftTypes->unionWith($rightTypes);
			} else {
				$leftNormalized = $leftTypes->normalize($scope);
				$rightNormalized = $rightTypes->normalize($rightScope);
				$types = $leftNormalized->intersectWith($rightNormalized);
				$types = $this->augmentDisjunctionTypes($scope, $rightScope, $leftNormalized, $rightNormalized, $expr->left, $expr->right, false, $types);
			}
			if ($context->false()) {
				$leftTypesForHolders = $leftTypes;
				$rightTypesForHolders = $rightTypes;
				if ($context->truthy()) {
					if ($leftTypesForHolders->getSureTypes() === [] && $leftTypesForHolders->getSureNotTypes() === []) {
						$leftTypesForHolders = $this->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createFalsey())->setRootExpr($expr);
					}
					if ($rightTypesForHolders->getSureTypes() === [] && $rightTypesForHolders->getSureNotTypes() === []) {
						$rightTypesForHolders = $this->specifyTypesInCondition($rightScope, $expr->right, TypeSpecifierContext::createFalsey())->setRootExpr($expr);
					}
				}
				$result = new SpecifiedTypes(
					$types->getSureTypes(),
					$types->getSureNotTypes(),
				);
				if ($types->shouldOverwrite()) {
					$result = $result->setAlwaysOverwriteTypes();
				}
				return $result->setNewConditionalExpressionHolders(array_merge(
					$this->processBooleanConditionalTypes($scope, $leftTypesForHolders, false, $rightTypesForHolders, false, $rightScope),
					$this->processBooleanConditionalTypes($scope, $rightTypesForHolders, false, $leftTypesForHolders, false, $scope),
					$this->processBooleanConditionalTypes($scope, $leftTypesForHolders, true, $rightTypesForHolders, true, $rightScope),
					$this->processBooleanConditionalTypes($scope, $rightTypesForHolders, true, $leftTypesForHolders, true, $scope),
				))->setRootExpr($expr);
			}

			return $types;
		} elseif ($expr instanceof BooleanOr || $expr instanceof LogicalOr) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}

			// For deep BooleanOr chains, flatten and process all arms at once
			// to avoid O(n^2) recursive filterByFalseyValue calls
			if (BooleanAndHandler::getBooleanExpressionDepth($expr) > self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
				return $this->specifyTypesForFlattenedBooleanOr($scope, $expr, $context);
			}

			$leftTypes = $this->specifyTypesInCondition($scope, $expr->left, $context)->setRootExpr($expr);
			$rightScope = $scope->filterByFalseyValue($expr->left);
			$rightTypes = $this->specifyTypesInCondition($rightScope, $expr->right, $context)->setRootExpr($expr);

			if ($context->true()) {
				if (
					$scope->getType($expr->left)->toBoolean()->isFalse()->yes()
				) {
					$types = $rightTypes->normalize($rightScope);
				} elseif (
					$scope->getType($expr->left)->toBoolean()->isTrue()->yes()
					|| $scope->getType($expr->right)->toBoolean()->isFalse()->yes()
				) {
					$types = $leftTypes->normalize($scope);
				} else {
					$leftNormalized = $leftTypes->normalize($scope);
					$rightNormalized = $rightTypes->normalize($rightScope);
					$types = $leftNormalized->intersectWith($rightNormalized);
					$types = $this->augmentBooleanOrTruthyWithConditionalHolders($scope, $rightScope, $expr, $types);
					$types = $this->augmentDisjunctionTypes($scope, $rightScope, $leftNormalized, $rightNormalized, $expr->left, $expr->right, true, $types);
				}
			} else {
				$types = $leftTypes->unionWith($rightTypes);
			}

			if ($context->true()) {
				$result = new SpecifiedTypes(
					$types->getSureTypes(),
					$types->getSureNotTypes(),
				);
				if ($types->shouldOverwrite()) {
					$result = $result->setAlwaysOverwriteTypes();
				}
				return $result->setNewConditionalExpressionHolders(array_merge(
					$this->processBooleanConditionalTypes($scope, $leftTypes, false, $rightTypes, false, $rightScope),
					$this->processBooleanConditionalTypes($scope, $rightTypes, false, $leftTypes, false, $scope),
					$this->processBooleanConditionalTypes($scope, $leftTypes, true, $rightTypes, true, $rightScope),
					$this->processBooleanConditionalTypes($scope, $rightTypes, true, $leftTypes, true, $scope),
				))->setRootExpr($expr);
			}

			return $types;
		} elseif ($expr instanceof Node\Expr\BooleanNot && !$context->null()) {
			return $this->specifyTypesInCondition($scope, $expr->expr, $context->negate())->setRootExpr($expr);
		} elseif ($expr instanceof Node\Expr\Assign) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}

			if ($context->null()) {
				$specifiedTypes = $this->specifyTypesInCondition($scope->exitFirstLevelStatements(), $expr->expr, $context)->setRootExpr($expr);
				$specifiedTypes = $specifiedTypes->removeExpr($this->exprPrinter->printExpr($expr->var));
			} else {
				$specifiedTypes = $this->specifyTypesInCondition($scope->exitFirstLevelStatements(), $expr->var, $context)->setRootExpr($expr);
			}

			// infer $arr[$key] after $key = array_key_first/last($arr)
			if (
				$expr->expr instanceof FuncCall
				&& $expr->expr->name instanceof Name
				&& !$expr->expr->isFirstClassCallable()
				&& in_array($expr->expr->name->toLowerString(), ['array_key_first', 'array_key_last'], true)
				&& count($expr->expr->getArgs()) >= 1
			) {
				$arrayArg = $expr->expr->getArgs()[0]->value;
				$arrayType = $scope->getType($arrayArg);

				if ($arrayType->isArray()->yes()) {
					if ($context->true()) {
						$specifiedTypes = $specifiedTypes->unionWith(
							$this->create($arrayArg, new NonEmptyArrayType(), TypeSpecifierContext::createTrue(), $scope),
						);
						$isNonEmpty = true;
					} else {
						$isNonEmpty = $arrayType->isIterableAtLeastOnce()->yes();
					}

					if ($isNonEmpty) {
						$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);
						$specifiedTypes = $specifiedTypes->unionWith(
							$this->create($dimFetch, $arrayType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope),
						);
					} elseif ($expr->var instanceof Expr\Variable && is_string($expr->var->name)) {
						$keyType = $scope->getType($expr->expr);
						$nonNullKeyType = TypeCombinator::removeNull($keyType);
						if (!$nonNullKeyType instanceof NeverType) {
							$specifiedTypes = $specifiedTypes->unionWith(
								$this->createArrayDimFetchConditionalExpressionHolder($expr->var, $arrayArg, $nonNullKeyType, $arrayType->getIterableValueType()),
							);
						}
					}
				}
			}

			// infer $arr[$key] after $key = array_search($needle, $arr) or $key = array_find_key($arr, $callback)
			if (
				$expr->expr instanceof FuncCall
				&& $expr->expr->name instanceof Name
				&& !$expr->expr->isFirstClassCallable()
				&& count($expr->expr->getArgs()) >= 2
			) {
				$funcName = $expr->expr->name->toLowerString();
				$arrayArg = null;
				$sentinelType = null;
				$isStrictArraySearch = false;

				if ($funcName === 'array_search') {
					$arrayArg = $expr->expr->getArgs()[1]->value;
					$sentinelType = new ConstantBooleanType(false);
					$isStrictArraySearch = count($expr->expr->getArgs()) >= 3 && $scope->getType($expr->expr->getArgs()[2]->value)->isTrue()->yes();
				} elseif ($funcName === 'array_find_key') {
					$arrayArg = $expr->expr->getArgs()[0]->value;
					$sentinelType = new NullType();
				}

				if ($arrayArg !== null) {
					$arrayType = $scope->getType($arrayArg);

					if ($arrayType->isArray()->yes()) {
						if ($context->true()) {
							$specifiedTypes = $specifiedTypes->unionWith(
								$this->create($arrayArg, new NonEmptyArrayType(), TypeSpecifierContext::createTrue(), $scope),
							);

							$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);

							if ($isStrictArraySearch) {
								$needleType = $scope->getType($expr->expr->getArgs()[0]->value);
								$dimFetchType = TypeCombinator::intersect($needleType, $arrayType->getIterableValueType());
							} else {
								$dimFetchType = $arrayType->getIterableValueType();
							}

							$specifiedTypes = $specifiedTypes->unionWith(
								$this->create($dimFetch, $dimFetchType, TypeSpecifierContext::createTrue(), $scope),
							);
						} elseif ($expr->var instanceof Expr\Variable && is_string($expr->var->name)) {
							$keyType = $scope->getType($expr->expr);
							$narrowedKeyType = TypeCombinator::remove($keyType, $sentinelType);
							if (!$narrowedKeyType instanceof NeverType) {
								if ($isStrictArraySearch) {
									$needleType = $scope->getType($expr->expr->getArgs()[0]->value);
									$dimFetchType = TypeCombinator::intersect($needleType, $arrayType->getIterableValueType());
								} else {
									$dimFetchType = $arrayType->getIterableValueType();
								}
								$specifiedTypes = $specifiedTypes->unionWith(
									$this->createArrayDimFetchConditionalExpressionHolder($expr->var, $arrayArg, $narrowedKeyType, $dimFetchType),
								);
							}
						}
					}
				}
			}

			if ($context->null()) {
				// infer $arr[$key] after $key = array_rand($arr)
				if (
					$expr->expr instanceof FuncCall
					&& $expr->expr->name instanceof Name
					&& !$expr->expr->isFirstClassCallable()
					&& in_array($expr->expr->name->toLowerString(), ['array_rand'], true)
					&& count($expr->expr->getArgs()) >= 1
				) {
					$numArg = null;
					$args = $expr->expr->getArgs();
					$arrayArg = $args[0]->value;
					if (count($args) > 1) {
						$numArg = $args[1]->value;
					}
					$one = new ConstantIntegerType(1);
					$arrayType = $scope->getType($arrayArg);

					if (
						$arrayType->isArray()->yes()
						&& $arrayType->isIterableAtLeastOnce()->yes()
						&& ($numArg === null || $one->isSuperTypeOf($scope->getType($numArg))->yes())
					) {
						$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);

						return $specifiedTypes->unionWith(
							$this->create($dimFetch, $arrayType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope),
						);
					}
				}

				// infer $list[$count] after $count = count($list) - 1
				if (
					$expr->expr instanceof Expr\BinaryOp\Minus
					&& $expr->expr->left instanceof FuncCall
					&& $expr->expr->left->name instanceof Name
					&& !$expr->expr->left->isFirstClassCallable()
					&& $expr->expr->right instanceof Node\Scalar\Int_
					&& $expr->expr->right->value === 1
					&& in_array($expr->expr->left->name->toLowerString(), ['count', 'sizeof'], true)
					&& count($expr->expr->left->getArgs()) >= 1
				) {
					$arrayArg = $expr->expr->left->getArgs()[0]->value;
					$arrayType = $scope->getType($arrayArg);
					if (
						$arrayType->isList()->yes()
						&& $arrayType->isIterableAtLeastOnce()->yes()
					) {
						$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);

						return $specifiedTypes->unionWith(
							$this->create($dimFetch, $arrayType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope),
						);
					}
				}

				return $specifiedTypes;
			}

			return $specifiedTypes;
		} elseif (
			$expr instanceof Expr\Isset_
			&& count($expr->vars) > 0
			&& !$context->null()
		) {
			// rewrite multi param isset() to and-chained single param isset()
			if (count($expr->vars) > 1) {
				$issets = [];
				foreach ($expr->vars as $var) {
					$issets[] = new Expr\Isset_([$var], $expr->getAttributes());
				}

				$first = array_shift($issets);
				$andChain = null;
				foreach ($issets as $isset) {
					if ($andChain === null) {
						$andChain = new BooleanAnd($first, $isset);
						continue;
					}

					$andChain = new BooleanAnd($andChain, $isset);
				}

				if ($andChain === null) {
					throw new ShouldNotHappenException();
				}

				return $this->specifyTypesInCondition($scope, $andChain, $context)->setRootExpr($expr);
			}

			$issetExpr = $expr->vars[0];

			if (!$context->true()) {
				if (!$scope instanceof MutatingScope) {
					throw new ShouldNotHappenException();
				}

				$isset = $scope->issetCheck($issetExpr, static fn () => true);

				if ($isset === false) {
					return new SpecifiedTypes();
				}

				$type = $scope->getType($issetExpr);
				$isNullable = !$type->isNull()->no();
				$exprType = $this->create(
					$issetExpr,
					new NullType(),
					$context->negate(),
					$scope,
				)->setRootExpr($expr);

				if ($issetExpr instanceof Expr\Variable && is_string($issetExpr->name)) {
					if ($isset === true) {
						if ($isNullable) {
							return $exprType;
						}

						// variable cannot exist in !isset()
						return $exprType->unionWith($this->create(
							new IssetExpr($issetExpr),
							new NullType(),
							$context,
							$scope,
						))->setRootExpr($expr);
					}

					if ($isNullable) {
						// reduces variable certainty to maybe
						return $exprType->unionWith($this->create(
							new IssetExpr($issetExpr),
							new NullType(),
							$context->negate(),
							$scope,
						))->setRootExpr($expr);
					}

					// variable cannot exist in !isset()
					return $this->create(
						new IssetExpr($issetExpr),
						new NullType(),
						$context,
						$scope,
					)->setRootExpr($expr);
				}

				if ($isNullable && $isset === true) {
					return $exprType;
				}

				if (
					$issetExpr instanceof ArrayDimFetch
					&& $issetExpr->dim !== null
				) {
					$varType = $scope->getType($issetExpr->var);
					if (!$varType instanceof MixedType) {
						$dimType = $scope->getType($issetExpr->dim);

						if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
							$constantArrays = $varType->getConstantArrays();
							$typesToRemove = [];
							foreach ($constantArrays as $constantArray) {
								$hasOffset = $constantArray->hasOffsetValueType($dimType);
								if (!$hasOffset->yes() || !$constantArray->getOffsetValueType($dimType)->isNull()->no()) {
									continue;
								}

								$typesToRemove[] = $constantArray;
							}

							if ($typesToRemove !== []) {
								$typeToRemove = TypeCombinator::union(...$typesToRemove);

								$result = $this->create(
									$issetExpr->var,
									$typeToRemove,
									TypeSpecifierContext::createFalse(),
									$scope,
								)->setRootExpr($expr);

								if ($scope->hasExpressionType($issetExpr->var)->maybe()) {
									$result = $result->unionWith(
										$this->create(
											new IssetExpr($issetExpr->var),
											new NullType(),
											TypeSpecifierContext::createTruthy(),
											$scope,
										)->setRootExpr($expr),
									);
								}

								return $result;
							}
						}
					}
				}

				return new SpecifiedTypes();
			}

			$tmpVars = [$issetExpr];
			while (
				$issetExpr instanceof ArrayDimFetch
				|| $issetExpr instanceof PropertyFetch
				|| (
					$issetExpr instanceof StaticPropertyFetch
					&& $issetExpr->class instanceof Expr
				)
			) {
				if ($issetExpr instanceof StaticPropertyFetch) {
					/** @var Expr $issetExpr */
					$issetExpr = $issetExpr->class;
				} else {
					$issetExpr = $issetExpr->var;
				}
				$tmpVars[] = $issetExpr;
			}
			$vars = array_reverse($tmpVars);

			$types = new SpecifiedTypes();
			foreach ($vars as $var) {

				if ($var instanceof Expr\Variable && is_string($var->name)) {
					if ($scope->hasVariableType($var->name)->no()) {
						return (new SpecifiedTypes([], []))->setRootExpr($expr);
					}
				}

				if (
					$var instanceof ArrayDimFetch
					&& $var->dim !== null
					&& !$scope->getType($var->var) instanceof MixedType
				) {
					$dimType = $scope->getType($var->dim);

					if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
						$types = $types->unionWith(
							$this->create(
								$var->var,
								new HasOffsetType($dimType),
								$context,
								$scope,
							)->setRootExpr($expr),
						);
					} else {
						$varType = $scope->getType($var->var);

						$narrowedKey = AllowedArrayKeysTypes::narrowOffsetKeyType($varType, $dimType);
						if ($narrowedKey !== null) {
							$types = $types->unionWith(
								$this->create(
									$var->dim,
									$narrowedKey,
									$context,
									$scope,
								)->setRootExpr($expr),
							);
						}

						if ($varType->isArray()->yes()) {
							$types = $types->unionWith(
								$this->create(
									$var->var,
									new NonEmptyArrayType(),
									$context,
									$scope,
								)->setRootExpr($expr),
							);
						}
					}
				}

				if (
					$var instanceof PropertyFetch
					&& $var->name instanceof Node\Identifier
				) {
					$types = $types->unionWith(
						$this->create($var->var, new IntersectionType([
							new ObjectWithoutClassType(),
							new HasPropertyType($var->name->toString()),
						]), TypeSpecifierContext::createTruthy(), $scope)->setRootExpr($expr),
					);
				} elseif (
					$var instanceof StaticPropertyFetch
					&& $var->class instanceof Expr
					&& $var->name instanceof Node\VarLikeIdentifier
				) {
					$types = $types->unionWith(
						$this->create($var->class, new IntersectionType([
							new ObjectWithoutClassType(),
							new HasPropertyType($var->name->toString()),
						]), TypeSpecifierContext::createTruthy(), $scope)->setRootExpr($expr),
					);
				}

				$types = $types->unionWith(
					$this->create($var, new NullType(), TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr),
				);
			}

			return $types;
		} elseif (
			$expr instanceof Expr\BinaryOp\Coalesce
			&& !$context->null()
		) {
			if (!$context->true()) {
				if (!$scope instanceof MutatingScope) {
					throw new ShouldNotHappenException();
				}

				$isset = $scope->issetCheck($expr->left, static fn () => true);

				if ($isset !== true) {
					return new SpecifiedTypes();
				}

				return $this->create(
					$expr->left,
					new NullType(),
					$context->negate(),
					$scope,
				)->setRootExpr($expr);
			}

			if ((new ConstantBooleanType(false))->isSuperTypeOf($scope->getType($expr->right)->toBoolean())->yes()) {
				return $this->create(
					$expr->left,
					new NullType(),
					TypeSpecifierContext::createFalse(),
					$scope,
				)->setRootExpr($expr);
			}

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
			), $context)->setRootExpr($expr);
		} elseif ($expr instanceof Expr\ErrorSuppress) {
			return $this->specifyTypesInCondition($scope, $expr->expr, $context)->setRootExpr($expr);
		} elseif (
			$expr instanceof Expr\Ternary
			&& !$expr->cond instanceof Expr\Ternary
			&& !$context->null()
		) {
			if ($expr->if !== null) {
				$conditionExpr = new BooleanOr(
					new BooleanAnd($expr->cond, $expr->if),
					new BooleanAnd(new Expr\BooleanNot($expr->cond), $expr->else),
				);
			} else {
				$conditionExpr = new BooleanOr(
					$expr->cond,
					new BooleanAnd(new Expr\BooleanNot($expr->cond), $expr->else),
				);
			}

			return $this->specifyTypesInCondition($scope, $conditionExpr, $context)->setRootExpr($expr);

		} elseif ($expr instanceof Expr\NullsafePropertyFetch && !$context->null()) {
			$types = $this->specifyTypesInCondition(
				$scope,
				new BooleanAnd(
					new Expr\BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					new PropertyFetch($expr->var, $expr->name),
				),
				$context,
			)->setRootExpr($expr);

			$nullSafeTypes = $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
			return $context->true() ? $types->unionWith($nullSafeTypes) : $types->normalize($scope)->intersectWith($nullSafeTypes->normalize($scope));
		} elseif ($expr instanceof Expr\NullsafeMethodCall && !$context->null()) {
			$types = $this->specifyTypesInCondition(
				$scope,
				new BooleanAnd(
					new Expr\BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					new MethodCall($expr->var, $expr->name, $expr->args),
				),
				$context,
			)->setRootExpr($expr);

			$nullSafeTypes = $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
			return $context->true() ? $types->unionWith($nullSafeTypes) : $types->normalize($scope)->intersectWith($nullSafeTypes->normalize($scope));
		} elseif (
			$expr instanceof Expr\New_
			&& $expr->class instanceof Name
			&& $this->reflectionProvider->hasClass($expr->class->toString())
		) {
			$classReflection = $this->reflectionProvider->getClass($expr->class->toString());

			if ($classReflection->hasConstructor()) {
				$methodReflection = $classReflection->getConstructor();
				$asserts = $methodReflection->getAsserts();

				if ($asserts->getAll() !== []) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $expr->getArgs(), $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants());

					$asserts = $asserts->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
						$type,
						$parametersAcceptor->getResolvedTemplateTypeMap(),
						$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						TemplateTypeVariance::createInvariant(),
					));

					$specifiedTypes = $this->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);

					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}
			}
		} elseif (!$context->null()) {
			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

	private function isNormalCountCall(FuncCall $countFuncCall, Type $typeToCount, Scope $scope): TrinaryLogic
	{
		if (count($countFuncCall->getArgs()) === 1) {
			return TrinaryLogic::createYes();
		}

		$mode = $scope->getType($countFuncCall->getArgs()[1]->value);
		return (new ConstantIntegerType(COUNT_NORMAL))->isSuperTypeOf($mode)->result->or($typeToCount->getIterableValueType()->isArray()->negate());
	}

	private function specifyTypesForCountFuncCall(
		FuncCall $countFuncCall,
		Type $type,
		Type $sizeType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		$isConstantArray = $type->isConstantArray();
		$isList = $type->isList();
		$oneOrMore = IntegerRangeType::fromInterval(1, null);
		if (
			!$this->isNormalCountCall($countFuncCall, $type, $scope)->yes()
			|| (!$isConstantArray->yes() && !$isList->yes())
			|| !$oneOrMore->isSuperTypeOf($sizeType)->yes()
			|| $sizeType->isSuperTypeOf($type->getArraySize())->yes()
		) {
			return null;
		}

		if ($context->falsey() && $isConstantArray->yes()) {
			$remainingSize = TypeCombinator::remove($type->getArraySize(), $sizeType);
			if (!$remainingSize instanceof NeverType) {
				$negatedContext = $context->false()
					? TypeSpecifierContext::createTrue()
					: TypeSpecifierContext::createTruthy();
				$result = $this->specifyTypesForCountFuncCall(
					$countFuncCall,
					$type,
					$remainingSize,
					$negatedContext,
					$scope,
					$rootExpr,
				);
				if ($result !== null) {
					return $result;
				}
			}

			// Fallback: directly filter constant arrays by their exact sizes.
			// This avoids using TypeCombinator::remove() with falsey context,
			// which can incorrectly remove arrays whose count doesn't match
			// but whose shape is a subtype of the matched array.
			$keptTypes = [];
			foreach ($type->getConstantArrays() as $arrayType) {
				if ($sizeType->isSuperTypeOf($arrayType->getArraySize())->yes()) {
					continue;
				}

				$keptTypes[] = $arrayType;
			}
			if ($keptTypes !== []) {
				return $this->create(
					$countFuncCall->getArgs()[0]->value,
					TypeCombinator::union(...$keptTypes),
					$context->negate(),
					$scope,
				)->setRootExpr($rootExpr);
			}
		}

		$resultTypes = [];
		foreach ($type->getArrays() as $arrayType) {
			$isSizeSuperTypeOfArraySize = $sizeType->isSuperTypeOf($arrayType->getArraySize());
			if ($isSizeSuperTypeOfArraySize->no()) {
				continue;
			}

			if ($context->falsey() && $isSizeSuperTypeOfArraySize->maybe()) {
				continue;
			}

			// `truncateListToSize` rebuilds the inner array as a list shape
			// — that's only sound when the *outer* type is definitely a
			// list. The inner array alone may have `isList()` answer `Maybe`
			// (e.g. `ArrayType<int<0, max>, T>` inside a
			// `non-empty-list<T>` intersection), so the gate has to live
			// here, not on the per-array method.
			$resultTypes[] = $isList->yes()
				? $arrayType->truncateListToSize($sizeType)
				: TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		if ($context->truthy() && $isConstantArray->yes() && $isList->yes()) {
			$hasOptionalKeys = false;
			foreach ($type->getConstantArrays() as $arrayType) {
				if ($arrayType->getOptionalKeys() !== []) {
					$hasOptionalKeys = true;
					break;
				}
			}

			if (!$hasOptionalKeys) {
				$argExpr = $countFuncCall->getArgs()[0]->value;
				$argExprString = $this->exprPrinter->printExpr($argExpr);

				$sizeMin = null;
				$sizeMax = null;
				if ($sizeType instanceof ConstantIntegerType) {
					$sizeMin = $sizeType->getValue();
					$sizeMax = $sizeType->getValue();
				} elseif ($sizeType instanceof IntegerRangeType) {
					$sizeMin = $sizeType->getMin();
					$sizeMax = $sizeType->getMax();
				}

				$sureTypes = [];
				$sureNotTypes = [];

				if ($sizeMin !== null && $sizeMin >= 1) {
					$sureTypes[$argExprString] = [$argExpr, new HasOffsetValueType(new ConstantIntegerType($sizeMin - 1), new MixedType())];
				}
				if ($sizeMax !== null) {
					$sureNotTypes[$argExprString] = [$argExpr, new HasOffsetValueType(new ConstantIntegerType($sizeMax), new MixedType())];
				}

				if ($sureTypes !== [] || $sureNotTypes !== []) {
					return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($rootExpr);
				}
			}
		}

		return $this->create($countFuncCall->getArgs()[0]->value, TypeCombinator::union(...$resultTypes), $context, $scope)->setRootExpr($rootExpr);
	}

	private function specifyTypesForConstantBinaryExpression(
		Expr $exprNode,
		Type $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		if (!$context->null() && $constantType->isFalse()->yes()) {
			$types = $this->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
			if (!$context->true() && ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			return $types->unionWith($this->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createFalse()->negate(),
			)->setRootExpr($rootExpr));
		}

		if (!$context->null() && $constantType->isTrue()->yes()) {
			$types = $this->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
			if (!$context->true() && ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			return $types->unionWith($this->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createTrue()->negate(),
			)->setRootExpr($rootExpr));
		}

		return null;
	}

	private function specifyTypesForConstantStringBinaryExpression(
		Expr $exprNode,
		Type $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		$scalarValues = $constantType->getConstantScalarValues();
		if (count($scalarValues) !== 1 || !is_string($scalarValues[0])) {
			return null;
		}
		$constantStringValue = $scalarValues[0];

		if (
			$exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& !$exprNode->isFirstClassCallable()
			&& strtolower($exprNode->name->toString()) === 'gettype'
			&& isset($exprNode->getArgs()[0])
		) {
			$type = null;
			if ($constantStringValue === 'string') {
				$type = new StringType();
			}
			if ($constantStringValue === 'array') {
				$type = new ArrayType(new MixedType(), new MixedType());
			}
			if ($constantStringValue === 'boolean') {
				$type = new BooleanType();
			}
			if (in_array($constantStringValue, ['resource', 'resource (closed)'], true)) {
				$type = new ResourceType();
			}
			if ($constantStringValue === 'integer') {
				$type = new IntegerType();
			}
			if ($constantStringValue === 'double') {
				$type = new FloatType();
			}
			if ($constantStringValue === 'NULL') {
				$type = new NullType();
			}
			if ($constantStringValue === 'object') {
				$type = new ObjectWithoutClassType();
			}

			if ($type !== null) {
				$callType = $this->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
				$argType = $this->create($exprNode->getArgs()[0]->value, $type, $context, $scope)->setRootExpr($rootExpr);
				return $callType->unionWith($argType);
			}
		}

		if (
			$context->true()
			&& $exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& !$exprNode->isFirstClassCallable()
			&& strtolower((string) $exprNode->name) === 'get_parent_class'
			&& isset($exprNode->getArgs()[0])
		) {
			$argType = $scope->getType($exprNode->getArgs()[0]->value);
			$objectType = new ObjectType($constantStringValue);
			$classStringType = new GenericClassStringType($objectType);

			if ($argType->isString()->yes()) {
				return $this->create(
					$exprNode->getArgs()[0]->value,
					$classStringType,
					$context,
					$scope,
				)->setRootExpr($rootExpr);
			}

			if ($argType->isObject()->yes()) {
				return $this->create(
					$exprNode->getArgs()[0]->value,
					$objectType,
					$context,
					$scope,
				)->setRootExpr($rootExpr);
			}

			return $this->create(
				$exprNode->getArgs()[0]->value,
				TypeCombinator::union($objectType, $classStringType),
				$context,
				$scope,
			)->setRootExpr($rootExpr);
		}

		if (
			$context->false()
			&& $exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& !$exprNode->isFirstClassCallable()
			&& in_array(strtolower((string) $exprNode->name), [
				'trim', 'ltrim', 'rtrim', 'chop',
				'mb_trim', 'mb_ltrim', 'mb_rtrim',
			], true)
			&& isset($exprNode->getArgs()[0])
			&& $constantStringValue === ''
		) {
			$argValue = $exprNode->getArgs()[0]->value;
			$argType = $scope->getType($argValue);
			if ($argType->isString()->yes()) {
				return $this->create(
					$argValue,
					new IntersectionType([
						new StringType(),
						new AccessoryNonEmptyStringType(),
					]),
					$context->negate(),
					$scope,
				)->setRootExpr($rootExpr);
			}
		}

		return null;
	}

	private function handleDefaultTruthyOrFalseyContext(TypeSpecifierContext $context, Expr $expr, Scope $scope): SpecifiedTypes
	{
		if ($context->null()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}
		if (!$context->truthy()) {
			$type = StaticTypeFactory::truthy();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr);
		} elseif (!$context->falsey()) {
			$type = StaticTypeFactory::falsey();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
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

		$argumentExpr = null;
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

			if ($returnType->getParameterName() !== '$' . $paramName) {
				continue;
			}

			$argumentExpr = $arg->value;
		}

		if ($argumentExpr === null) {
			return null;
		}

		return $this->getConditionalSpecifiedTypes($returnType, $leftType, $rightType, $scope, $argumentExpr);
	}

	private function getConditionalSpecifiedTypes(
		ConditionalTypeForParameter $conditionalType,
		Type $leftType,
		Type $rightType,
		Scope $scope,
		Expr $argumentExpr,
	): ?SpecifiedTypes
	{
		$targetType = $conditionalType->getTarget();
		$ifType = $conditionalType->getIf();
		$elseType = $conditionalType->getElse();

		if (
			(
				$argumentExpr instanceof Node\Scalar
				|| ($argumentExpr instanceof ConstFetch && in_array(strtolower($argumentExpr->name->toString()), ['true', 'false', 'null'], true))
			) && ($ifType instanceof NeverType || $elseType instanceof NeverType)
		) {
			return null;
		}

		if ($leftType->isSuperTypeOf($ifType)->yes() && $rightType->isSuperTypeOf($elseType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue();
		} elseif ($leftType->isSuperTypeOf($elseType)->yes() && $rightType->isSuperTypeOf($ifType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
		} else {
			return null;
		}

		$specifiedTypes = $this->create(
			$argumentExpr,
			$targetType,
			$context,
			$scope,
		);

		if ($targetType instanceof ConstantBooleanType) {
			if (!$targetType->getValue()) {
				$context = $context->negate();
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->specifyTypesInCondition($scope, $argumentExpr, $context));
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
				$lastParameter = array_last($parameters);
				$paramName = $lastParameter->getName();
			} else {
				continue;
			}

			$argsMap[$paramName][] = $arg->value;
		}
		foreach ($parameters as $parameter) {
			$name = $parameter->getName();
			$defaultValue = $parameter->getDefaultValue();
			if (isset($argsMap[$name]) || $defaultValue === null) {
				continue;
			}
			$argsMap[$name][] = new TypeExpr($defaultValue);
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
							$type = $traverse($type);
							if ($type instanceof ConditionalTypeForParameter) {
								$argType = TypeCombinator::union(...array_map(static fn (Expr $expr) => $scope->getType($expr), $argsMap[substr($type->getParameterName(), 1)]));
								return $type->toConditional($argType);
							}
							return $type;
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
					$scope,
				)->setRootExpr($containsUnresolvedTemplate || $assert->isEquality() ? $call : null);
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

	private function specifyTypesFromCallableCall(TypeSpecifierContext $context, FuncCall $call, Scope $scope): ?SpecifiedTypes
	{
		if (!$call->name instanceof Expr) {
			return null;
		}

		$calleeType = $scope->getType($call->name);

		$assertions = null;
		$parametersAcceptor = null;
		if ($calleeType->isCallable()->yes()) {
			$variants = $calleeType->getCallableParametersAcceptors($scope);
			$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $call->getArgs(), $variants);
			if ($parametersAcceptor instanceof CallableParametersAcceptor) {
				$assertions = $parametersAcceptor->getAsserts();
			}
		}

		if ($assertions === null || $assertions->getAll() === []) {
			return null;
		}

		$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$parametersAcceptor->getResolvedTemplateTypeMap(),
			$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		));

		return $this->specifyTypesFromAsserts($context, $call, $asserts, $parametersAcceptor, $scope);
	}

	/**
	 * For `if ($a || $b)` truthy, expressions narrowed by stored conditional
	 * holders (e.g. `$a = $obj instanceof ClassA;` records "when `$a` is
	 * truthy, `$obj` is `ClassA`") need to be projected into the OR-truthy
	 * scope as the union of the per-arm narrowings. specifyTypesInCondition
	 * for each arm only looks at the boolean variable itself, so the held
	 * narrowing of `$obj` would otherwise be invisible until a later check
	 * pins one of the booleans down.
	 *
	 * For each conditional-holder target $T:
	 * - resolve $T's type in the left-truthy and right-truthy filtered scopes
	 * - if both narrow $T strictly below the original, add `$T : leftT|rightT`
	 *   as a sure type to the OR-truthy result
	 *
	 * The asymmetric case (one arm narrows, the other doesn't) is intentionally
	 * skipped: in the OR-truthy scope the arm that didn't narrow could still be
	 * the truthy one, so the sound result is the original (unnarrowed) type.
	 */
	private function augmentBooleanOrTruthyWithConditionalHolders(MutatingScope $scope, MutatingScope $rightScope, BooleanOr|LogicalOr $expr, SpecifiedTypes $types): SpecifiedTypes
	{
		$leftTruthyScope = $scope->filterByTruthyValue($expr->left);
		$rightTruthyScope = $rightScope->filterByTruthyValue($expr->right);

		$seen = [];
		foreach ([$scope, $rightScope] as $sourceScope) {
			foreach ($sourceScope->getConditionalExpressions() as $exprString => $holders) {
				if (isset($seen[$exprString])) {
					continue;
				}
				if ($holders === []) {
					continue;
				}
				$seen[$exprString] = true;
				$targetExpr = $holders[array_key_first($holders)]->getTypeHolder()->getExpr();

				// Only project when the target stays Yes-defined in the original
				// scope and in both filtered branches. A sure type implicitly
				// raises certainty to Yes, which would wrongly upgrade Maybe-defined
				// variables — `if (empty($a['bar']))` for instance leaves `$a`
				// Maybe-defined because `empty()` tolerates undefined offsets.
				if (!$scope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}
				if (!$leftTruthyScope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}
				if (!$rightTruthyScope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}

				$origType = $scope->getType($targetExpr);
				$leftType = $leftTruthyScope->getType($targetExpr);
				$rightType = $rightTruthyScope->getType($targetExpr);

				$leftNarrowed = !$leftType->equals($origType) && $origType->isSuperTypeOf($leftType)->yes();
				$rightNarrowed = !$rightType->equals($origType) && $origType->isSuperTypeOf($rightType)->yes();

				if (!$leftNarrowed || !$rightNarrowed) {
					continue;
				}

				$unionType = TypeCombinator::union($leftType, $rightType);
				if ($unionType->equals($origType)) {
					continue;
				}

				$types = $types->unionWith(
					$this->create($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope),
				);
			}
		}

		return $types;
	}

	private function augmentDisjunctionTypes(
		MutatingScope $scope,
		MutatingScope $rightScope,
		SpecifiedTypes $leftNormalized,
		SpecifiedTypes $rightNormalized,
		Expr $leftExpr,
		Expr $rightExpr,
		bool $truthy,
		SpecifiedTypes $types,
	): SpecifiedTypes
	{
		$candidateExprs = [];
		foreach ($leftNormalized->getSureTypes() as $exprString => [$exprNode, $type]) {
			$candidateExprs[$exprString] = $exprNode;
		}
		foreach ($rightNormalized->getSureTypes() as $exprString => [$exprNode, $type]) {
			$candidateExprs[$exprString] = $exprNode;
		}

		$existingSureTypes = $types->getSureTypes();

		$viableCandidates = [];
		foreach ($candidateExprs as $exprString => $targetExpr) {
			if (isset($existingSureTypes[$exprString])) {
				continue;
			}
			if (!$scope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}
			$viableCandidates[$exprString] = $targetExpr;
		}

		if ($viableCandidates === []) {
			return $types;
		}

		if ($truthy) {
			$leftFilteredScope = $scope->filterByTruthyValue($leftExpr);
			$rightFilteredScope = $rightScope->filterByTruthyValue($rightExpr);
		} else {
			$leftFilteredScope = $scope->filterByFalseyValue($leftExpr);
			$rightFilteredScope = $rightScope->filterByFalseyValue($rightExpr);
		}

		foreach ($viableCandidates as $targetExpr) {
			if (!$leftFilteredScope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}
			if (!$rightFilteredScope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}

			$originalType = $scope->getType($targetExpr);
			$leftType = $leftFilteredScope->getType($targetExpr);
			$rightType = $rightFilteredScope->getType($targetExpr);

			if ($leftType->equals($originalType) || !$originalType->isSuperTypeOf($leftType)->yes()) {
				continue;
			}

			if ($rightType->equals($originalType) || !$originalType->isSuperTypeOf($rightType)->yes()) {
				continue;
			}

			$unionType = TypeCombinator::union($leftType, $rightType);
			if ($unionType->equals($originalType)) {
				continue;
			}

			$types = $types->unionWith(
				$this->create($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope),
			);
		}

		return $types;
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processBooleanConditionalTypes(Scope $scope, SpecifiedTypes $conditionSpecifiedTypes, bool $conditionsFromSureTypes, SpecifiedTypes $holderSpecifiedTypes, bool $holdersFromSureTypes, Scope $rightScope): array
	{
		$conditionExpressionTypes = [];
		$conditionTypes = $conditionsFromSureTypes ? $conditionSpecifiedTypes->getSureTypes() : $conditionSpecifiedTypes->getSureNotTypes();
		foreach ($conditionTypes as $exprString => [$expr, $type]) {
			if (!$this->isTrackableExpression($expr)) {
				continue;
			}

			if ($conditionsFromSureTypes) {
				$scopeType = $scope->getType($expr);
				$conditionType = TypeCombinator::remove($scopeType, $type);
				if ($scopeType->equals($conditionType)) {
					continue;
				}
			} else {
				$conditionType = TypeCombinator::intersect($scope->getType($expr), $type);
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes(
				$expr,
				$conditionType,
			);
		}

		if (count($conditionExpressionTypes) > 0) {
			$holders = [];
			$holderTypes = $holdersFromSureTypes ? $holderSpecifiedTypes->getSureTypes() : $holderSpecifiedTypes->getSureNotTypes();
			foreach ($holderTypes as $exprString => [$expr, $type]) {
				if (!$this->isTrackableExpression($expr)) {
					continue;
				}

				if (!isset($holders[$exprString])) {
					$holders[$exprString] = [];
				}

				$conditions = $conditionExpressionTypes;
				foreach (array_keys($conditions) as $conditionExprString) {
					if ($conditionExprString !== $exprString) {
						continue;
					}
					unset($conditions[$conditionExprString]);
				}

				if (count($conditions) === 0) {
					continue;
				}

				$targetScope = $expr instanceof Expr\Variable ? $scope : $rightScope;
				$holderType = $holdersFromSureTypes
					? TypeCombinator::intersect($targetScope->getType($expr), $type)
					: TypeCombinator::remove($targetScope->getType($expr), $type);
				$holder = new ConditionalExpressionHolder(
					$conditions,
					ExpressionTypeHolder::createYes($expr, $holderType),
				);
				$holders[$exprString][$holder->getKey()] = $holder;
			}

			return $holders;
		}

		return [];
	}

	private function isTrackableExpression(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable) {
			return is_string($expr->name);
		}

		return $expr instanceof Expr\PropertyFetch
			|| $expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\StaticPropertyFetch;
	}

	/**
	 * Flatten a deep BooleanOr chain into leaf expressions and process them
	 * without recursive filterByFalseyValue calls. This reduces O(n^2) to O(n)
	 * for chains with many arms (e.g., 80+ === comparisons in ||).
	 */
	private function specifyTypesForFlattenedBooleanOr(
		MutatingScope $scope,
		BooleanOr|LogicalOr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		// Collect all leaf expressions from the chain
		$arms = [];
		$current = $expr;
		while ($current instanceof BooleanOr || $current instanceof LogicalOr) {
			$arms[] = $current->right;
			$current = $current->left;
		}
		$arms[] = $current; // leftmost leaf
		$arms = array_reverse($arms);

		if ($context->false() || $context->falsey()) {
			// Falsey: all arms are false → union all SpecifiedTypes.
			// Collect per-expression types first, then build unions once
			// to avoid O(N²) from incremental TypeCombinator::union() growth.
			/** @var array<string, array{Expr, list<Type>}> $sureTypesPerExpr */
			$sureTypesPerExpr = [];
			/** @var array<string, array{Expr, list<Type>}> $sureNotTypesPerExpr */
			$sureNotTypesPerExpr = [];

			foreach ($arms as $arm) {
				$armTypes = $this->specifyTypesInCondition($scope, $arm, $context);
				foreach ($armTypes->getSureTypes() as $exprString => [$exprNode, $type]) {
					$sureTypesPerExpr[$exprString][0] = $exprNode;
					$sureTypesPerExpr[$exprString][1][] = $type;
				}
				foreach ($armTypes->getSureNotTypes() as $exprString => [$exprNode, $type]) {
					$sureNotTypesPerExpr[$exprString][0] = $exprNode;
					$sureNotTypesPerExpr[$exprString][1][] = $type;
				}
			}

			$sureTypes = [];
			foreach ($sureTypesPerExpr as $exprString => [$exprNode, $types]) {
				$sureTypes[$exprString] = [$exprNode, TypeCombinator::intersect(...$types)];
			}
			$sureNotTypes = [];
			foreach ($sureNotTypesPerExpr as $exprString => [$exprNode, $types]) {
				$sureNotTypes[$exprString] = [$exprNode, TypeCombinator::union(...$types)];
			}

			return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($expr);
		}

		// Truthy: at least one arm is true → intersect all normalized SpecifiedTypes
		$armSpecifiedTypes = [];
		foreach ($arms as $arm) {
			$armTypes = $this->specifyTypesInCondition($scope, $arm, $context);
			$armSpecifiedTypes[] = $armTypes->normalize($scope);
		}

		$types = $armSpecifiedTypes[0];
		for ($i = 1; $i < count($armSpecifiedTypes); $i++) {
			$types = $types->intersectWith($armSpecifiedTypes[$i]);
		}

		$result = new SpecifiedTypes(
			$types->getSureTypes(),
			$types->getSureNotTypes(),
		);
		if ($types->shouldOverwrite()) {
			$result = $result->setAlwaysOverwriteTypes();
		}

		return $result->setRootExpr($expr);
	}

	/**
	 * @param BooleanAnd|LogicalAnd $expr
	 */
	private function specifyTypesForFlattenedBooleanAnd(
		MutatingScope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$arms = [];
		$current = $expr;
		while ($current instanceof BooleanAnd || $current instanceof LogicalAnd) {
			$arms[] = $current->right;
			$current = $current->left;
		}
		$arms[] = $current;
		$arms = array_reverse($arms);

		// Truthy: all arms are true → union all SpecifiedTypes.
		// Collect per-expression types first, then build unions once
		// to avoid O(N²) from incremental growth.
		/** @var array<string, array{Expr, list<Type>}> $sureTypesPerExpr */
		$sureTypesPerExpr = [];
		/** @var array<string, array{Expr, list<Type>}> $sureNotTypesPerExpr */
		$sureNotTypesPerExpr = [];

		foreach ($arms as $arm) {
			$armTypes = $this->specifyTypesInCondition($scope, $arm, $context);
			foreach ($armTypes->getSureTypes() as $exprString => [$exprNode, $type]) {
				$sureTypesPerExpr[$exprString][0] = $exprNode;
				$sureTypesPerExpr[$exprString][1][] = $type;
			}
			foreach ($armTypes->getSureNotTypes() as $exprString => [$exprNode, $type]) {
				$sureNotTypesPerExpr[$exprString][0] = $exprNode;
				$sureNotTypesPerExpr[$exprString][1][] = $type;
			}
		}

		$sureTypes = [];
		foreach ($sureTypesPerExpr as $exprString => [$exprNode, $types]) {
			$sureTypes[$exprString] = [$exprNode, TypeCombinator::union(...$types)];
		}
		$sureNotTypes = [];
		foreach ($sureNotTypesPerExpr as $exprString => [$exprNode, $types]) {
			$sureNotTypes[$exprString] = [$exprNode, TypeCombinator::union(...$types)];
		}

		return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($expr);
	}

	/**
	 * @return array{Expr, ConstantScalarType, Type}|null
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
		) {
			return [$binaryOperation->right, $leftType, $rightType];
		} elseif (
			$rightType instanceof ConstantScalarType
			&& !$leftExpr instanceof ConstFetch
		) {
			return [$binaryOperation->left, $rightType, $leftType];
		}

		return null;
	}

	/**
	 * @api
	 */
	public function create(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		if ($expr instanceof Instanceof_ || $expr instanceof Expr\List_) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		$specifiedExprs = [];
		if ($expr instanceof AlwaysRememberedExpr) {
			$specifiedExprs[] = $expr;
			$expr = $expr->expr;
		}

		if ($expr instanceof Expr\Assign) {
			$specifiedExprs[] = $expr->var;
			$specifiedExprs[] = $expr->expr;

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
			$newTypes = $this->createForExpr($specifiedExpr, $type, $context, $scope);

			if ($types === null) {
				$types = $newTypes;
			} else {
				$types = $types->unionWith($newTypes);
			}
		}

		return $types;
	}

	private function createArrayDimFetchConditionalExpressionHolder(
		Expr\Variable $keyVar,
		Expr $arrayArg,
		Type $narrowedKeyType,
		Type $dimFetchType,
	): SpecifiedTypes
	{
		$dimFetch = new ArrayDimFetch($arrayArg, $keyVar);
		$dimFetchString = $this->exprPrinter->printExpr($dimFetch);
		$keyExprString = $this->exprPrinter->printExpr($keyVar);

		$holder = new ConditionalExpressionHolder(
			[$keyExprString => ExpressionTypeHolder::createYes($keyVar, $narrowedKeyType)],
			ExpressionTypeHolder::createYes($dimFetch, $dimFetchType),
		);

		return (new SpecifiedTypes([], []))->setNewConditionalExpressionHolders([
			$dimFetchString => [$holder->getKey() => $holder],
		]);
	}

	private function createForExpr(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		if ($context->true()) {
			$containsNull = !$type->isNull()->no() && !$scope->getType($expr)->isNull()->no();
		} elseif ($context->false()) {
			$containsNull = !TypeCombinator::containsNull($type) && !$scope->getType($expr)->isNull()->no();
		}

		$originalExpr = $expr;
		if (isset($containsNull) && !$containsNull) {
			$expr = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr);
		}

		if (
			!$context->null()
			&& $expr instanceof Expr\BinaryOp\Coalesce
		) {
			if (
				($context->true() && $type->isSuperTypeOf($scope->getType($expr->right))->no())
				|| ($context->false() && $type->isSuperTypeOf($scope->getType($expr->right))->yes())
			) {
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
				return new SpecifiedTypes([], []);
			}

			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$hasSideEffects = $functionReflection->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return new SpecifiedTypes([], []);
			}

			if (!$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no()) {
				return new SpecifiedTypes([], []);
			}
		}

		if (
			$expr instanceof FuncCall
			&& !$expr->name instanceof Name
		) {
			$nameType = $scope->getType($expr->name);
			if ($nameType->isCallable()->yes()) {
				$isPure = null;
				foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
					$variantIsPure = $variant->isPure();
					$isPure = $isPure === null ? $variantIsPure : $isPure->and($variantIsPure);
				}

				if ($isPure !== null) {
					if ($isPure->no()) {
						return new SpecifiedTypes([], []);
					}

					if (!$this->rememberPossiblyImpureFunctionValues && !$isPure->yes()) {
						return new SpecifiedTypes([], []);
					}
				}
			}
		}

		if (
			$expr instanceof MethodCall
			&& $expr->name instanceof Node\Identifier
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
					return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
				}

				return new SpecifiedTypes([], []);
			}
		}

		if (
			$expr instanceof StaticCall
			&& $expr->name instanceof Node\Identifier
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
					return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
				}

				return new SpecifiedTypes([], []);
			}
		}

		$sureTypes = [];
		$sureNotTypes = [];
		if ($context->false()) {
			$exprString = $this->exprPrinter->printExpr($expr);
			$sureNotTypes[$exprString] = [$expr, $type];

			if ($expr !== $originalExpr) {
				$originalExprString = $this->exprPrinter->printExpr($originalExpr);
				$sureNotTypes[$originalExprString] = [$originalExpr, $type];
			}
		} elseif ($context->true()) {
			$exprString = $this->exprPrinter->printExpr($expr);
			$sureTypes[$exprString] = [$expr, $type];

			if ($expr !== $originalExpr) {
				$originalExprString = $this->exprPrinter->printExpr($originalExpr);
				$sureTypes[$originalExprString] = [$originalExpr, $type];
			}
		}

		$types = new SpecifiedTypes($sureTypes, $sureNotTypes);
		if (isset($containsNull) && !$containsNull) {
			return $this->createNullsafeTypes($originalExpr, $scope, $context, $type)->unionWith($types);
		}

		return $types;
	}

	private function createNullsafeTypes(Expr $expr, Scope $scope, TypeSpecifierContext $context, ?Type $type): SpecifiedTypes
	{
		if ($expr instanceof Expr\NullsafePropertyFetch) {
			if ($type !== null) {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), $type, $context, $scope);
			} else {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), new NullType(), TypeSpecifierContext::createFalse(), $scope);
			}

			return $propertyFetchTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $scope),
			);
		}

		if ($expr instanceof Expr\NullsafeMethodCall) {
			if ($type !== null) {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), $type, $context, $scope);
			} else {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), new NullType(), TypeSpecifierContext::createFalse(), $scope);
			}

			return $methodCallTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $scope),
			);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\MethodCall) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($expr->class, $scope, $context, null);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($expr->class, $scope, $context, null);
		}

		return new SpecifiedTypes([], []);
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

		return (new SpecifiedTypes(sureNotTypes: $sureNotTypes))->setRootExpr($rootExpr);
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

	public function resolveEqual(Expr\BinaryOp\Equal $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
		if ($expressions !== null) {
			$exprNode = $expressions[0];
			$constantType = $expressions[1];
			$otherType = $expressions[2];

			if (!$context->null() && $constantType->getValue() === null) {
				$trueTypes = [
					new NullType(),
					new ConstantBooleanType(false),
					new ConstantIntegerType(0),
					new ConstantFloatType(0.0),
					new ConstantStringType(''),
					new ConstantArrayType([], []),
				];
				return $this->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === false) {
				return $this->specifyTypesInCondition(
					$scope,
					$exprNode,
					$context->true() ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createFalsey()->negate(),
				)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === true) {
				return $this->specifyTypesInCondition(
					$scope,
					$exprNode,
					$context->true() ? TypeSpecifierContext::createTruthy() : TypeSpecifierContext::createTruthy()->negate(),
				)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === 0 && !$otherType->isInteger()->yes() && !$otherType->isBoolean()->yes()) {
				/* There is a difference between php 7.x and 8.x on the equality
				 * behavior between zero and the empty string, so to be conservative
				 * we leave it untouched regardless of the language version */
				if ($context->true()) {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantFloatType(0.0),
						new StringType(),
					];
				} else {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantFloatType(0.0),
						new ConstantStringType('0'),
					];
				}
				return $this->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === '') {
				/* There is a difference between php 7.x and 8.x on the equality
				 * behavior between zero and the empty string, so to be conservative
				 * we leave it untouched regardless of the language version */
				if ($context->true()) {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantFloatType(0.0),
						new ConstantStringType(''),
					];
				} else {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantStringType(''),
					];
				}
				return $this->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (
				$exprNode instanceof FuncCall
				&& $exprNode->name instanceof Name
				&& !$exprNode->isFirstClassCallable()
				&& in_array(strtolower($exprNode->name->toString()), ['gettype', 'get_class', 'get_debug_type'], true)
				&& isset($exprNode->getArgs()[0])
				&& $constantType->isString()->yes()
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}

			if (
				$context->true()
				&& $exprNode instanceof FuncCall
				&& $exprNode->name instanceof Name
				&& $exprNode->name->toLowerString() === 'preg_match'
				&& (new ConstantIntegerType(1))->isSuperTypeOf($constantType)->yes()
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}

			if (
				$context->true()
				&& $exprNode instanceof ClassConstFetch
				&& $exprNode->name instanceof Node\Identifier
				&& strtolower($exprNode->name->toString()) === 'class'
				&& $constantType->isString()->yes()
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
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
			)->setRootExpr($expr);
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
			)->setRootExpr($expr);
		}

		if (
			!$context->null()
			&& $rightType->isArray()->yes()
			&& $leftType->isConstantArray()->yes() && $leftType->isIterableAtLeastOnce()->no()
		) {
			return $this->create($expr->right, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
		}

		if (
			!$context->null()
			&& $leftType->isArray()->yes()
			&& $rightType->isConstantArray()->yes() && $rightType->isIterableAtLeastOnce()->no()
		) {
			return $this->create($expr->left, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
		}

		if (
			($leftType->isString()->yes() && $rightType->isString()->yes())
			|| ($leftType->isInteger()->yes() && $rightType->isInteger()->yes())
			|| ($leftType->isFloat()->yes() && $rightType->isFloat()->yes())
			|| ($leftType->isEnum()->yes() && $rightType->isEnum()->yes())
		) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
		}

		$leftExprString = $this->exprPrinter->printExpr($expr->left);
		$rightExprString = $this->exprPrinter->printExpr($expr->right);
		if ($leftExprString === $rightExprString) {
			if (!$expr->left instanceof Expr\Variable || !$expr->right instanceof Expr\Variable) {
				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			}
		}

		$leftTypes = $this->create($expr->left, $leftType, $context, $scope)->setRootExpr($expr);
		$rightTypes = $this->create($expr->right, $rightType, $context, $scope)->setRootExpr($expr);

		return $context->true()
			? $leftTypes->unionWith($rightTypes)
			: $leftTypes->normalize($scope)->intersectWith($rightTypes->normalize($scope));
	}

	public function resolveIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$leftExpr = $expr->left;
		$rightExpr = $expr->right;

		// Normalize to: fn() === expr
		if ($rightExpr instanceof FuncCall && !$leftExpr instanceof FuncCall) {
			$specifiedTypes = $this->resolveNormalizedIdentical(new Expr\BinaryOp\Identical(
				$rightExpr,
				$leftExpr,
			), $scope, $context);
		} else {
			$specifiedTypes = $this->resolveNormalizedIdentical(new Expr\BinaryOp\Identical(
				$leftExpr,
				$rightExpr,
			), $scope, $context);
		}

		// merge result of fn1() === fn2() and fn2() === fn1()
		if ($rightExpr instanceof FuncCall && $leftExpr instanceof FuncCall) {
			return $specifiedTypes->unionWith(
				$this->resolveNormalizedIdentical(new Expr\BinaryOp\Identical(
					$rightExpr,
					$leftExpr,
				), $scope, $context),
			);
		}

		return $specifiedTypes;
	}

	private function resolveNormalizedIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$leftExpr = $expr->left;
		$rightExpr = $expr->right;

		$unwrappedLeftExpr = $leftExpr;
		if ($leftExpr instanceof AlwaysRememberedExpr) {
			$unwrappedLeftExpr = $leftExpr->getExpr();
		}
		$unwrappedRightExpr = $rightExpr;
		if ($rightExpr instanceof AlwaysRememberedExpr) {
			$unwrappedRightExpr = $rightExpr->getExpr();
		}

		$rightType = $scope->getType($rightExpr);

		// (count($a) === $expr)
		if (
			!$context->null()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& count($unwrappedLeftExpr->getArgs()) >= 1
			&& $unwrappedLeftExpr->name instanceof Name
			&& in_array(strtolower((string) $unwrappedLeftExpr->name), ['count', 'sizeof'], true)
			&& $rightType->isInteger()->yes()
		) {
			// count($a) === count($b)
			if (
				$context->true()
				&& $unwrappedRightExpr instanceof FuncCall
				&& $unwrappedRightExpr->name instanceof Name
				&& !$unwrappedRightExpr->isFirstClassCallable()
				&& in_array($unwrappedRightExpr->name->toLowerString(), ['count', 'sizeof'], true)
				&& count($unwrappedRightExpr->getArgs()) >= 1
			) {
				$argType = $scope->getType($unwrappedRightExpr->getArgs()[0]->value);
				$sizeType = $scope->getType($leftExpr);

				$specifiedTypes = $this->specifyTypesForCountFuncCall($unwrappedRightExpr, $argType, $sizeType, $context, $scope, $expr);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}

				$leftArrayType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
				$rightArrayType = $scope->getType($unwrappedRightExpr->getArgs()[0]->value);
				if (
					$leftArrayType->isArray()->yes()
					&& $rightArrayType->isArray()->yes()
					&& !$rightType->isConstantScalarValue()->yes()
					&& ($leftArrayType->isIterableAtLeastOnce()->yes() || $rightArrayType->isIterableAtLeastOnce()->yes())
				) {
					$arrayTypes = $this->create($unwrappedLeftExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr);
					return $arrayTypes->unionWith(
						$this->create($unwrappedRightExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr),
					);
				}
			}

			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($rightType)->yes()) {
				return $this->create($unwrappedLeftExpr->getArgs()[0]->value, new NeverType(), $context, $scope)->setRootExpr($expr);
			}

			$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
			$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($rightType);
			if ($isZero->yes()) {
				$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);

				if ($context->truthy() && !$argType->isArray()->yes()) {
					$newArgType = new UnionType([
						new ObjectType(Countable::class),
						new ConstantArrayType([], []),
					]);
				} else {
					$newArgType = new ConstantArrayType([], []);
				}

				return $funcTypes->unionWith(
					$this->create($unwrappedLeftExpr->getArgs()[0]->value, $newArgType, $context, $scope)->setRootExpr($expr),
				);
			}

			$specifiedTypes = $this->specifyTypesForCountFuncCall($unwrappedLeftExpr, $argType, $rightType, $context, $scope, $expr);
			if ($specifiedTypes !== null) {
				if ($leftExpr !== $unwrappedLeftExpr) {
					$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
					return $specifiedTypes->unionWith($funcTypes);
				}
				return $specifiedTypes;
			}

			if ($context->truthy() && $argType->isArray()->yes()) {
				$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
				if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($rightType)->yes()) {
					return $funcTypes->unionWith(
						$this->create($unwrappedLeftExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr),
					);
				}

				return $funcTypes;
			}
		}

		// strlen($a) === $b
		if (
			!$context->null()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& in_array(strtolower((string) $unwrappedLeftExpr->name), ['strlen', 'mb_strlen'], true)
			&& count($unwrappedLeftExpr->getArgs()) === 1
			&& $rightType->isInteger()->yes()
		) {
			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($rightType)->yes()) {
				return $this->create($unwrappedLeftExpr->getArgs()[0]->value, new NeverType(), $context, $scope)->setRootExpr($expr);
			}

			$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($rightType);
			if ($isZero->yes()) {
				$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
				return $funcTypes->unionWith(
					$this->create($unwrappedLeftExpr->getArgs()[0]->value, new ConstantStringType(''), $context, $scope)->setRootExpr($expr),
				);
			}

			if ($context->truthy() && IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($rightType)->yes()) {
				$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
				if ($argType->isString()->yes()) {
					$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);

					$accessory = new AccessoryNonEmptyStringType();
					if (IntegerRangeType::fromInterval(2, null)->isSuperTypeOf($rightType)->yes()) {
						$accessory = new AccessoryNonFalsyStringType();
					}
					$valueTypes = $this->create($unwrappedLeftExpr->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($expr);

					return $funcTypes->unionWith($valueTypes);
				}
			}
		}

		// array_key_first($a) !== null
		// array_key_last($a) !== null
		// array_find_key($a, $cb) !== null
		if (
			$unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& isset($unwrappedLeftExpr->getArgs()[0])
			&& $rightType->isNull()->yes()
		) {
			$funcName = $unwrappedLeftExpr->name->toLowerString();
			$bothDirections = in_array($funcName, ['array_key_first', 'array_key_last'], true);
			$notNullOnly = $funcName === 'array_find_key';
			if ($bothDirections || $notNullOnly) {
				$args = $unwrappedLeftExpr->getArgs();
				$argType = $scope->getType($args[0]->value);
				if ($argType->isArray()->yes()) {
					if ($bothDirections) {
						return $this->create($args[0]->value, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
					}
					if ($context->falsey()) {
						return $this->create($args[0]->value, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
					}
				}
			}
		}

		// preg_match($a) === $b
		if (
			$context->true()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& $unwrappedLeftExpr->name->toLowerString() === 'preg_match'
			&& (new ConstantIntegerType(1))->isSuperTypeOf($rightType)->yes()
		) {
			return $this->specifyTypesInCondition(
				$scope,
				$leftExpr,
				$context,
			)->setRootExpr($expr);
		}

		// get_class($a) === 'Foo'
		if (
			$context->true()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& in_array(strtolower($unwrappedLeftExpr->name->toString()), ['get_class', 'get_debug_type'], true)
			&& isset($unwrappedLeftExpr->getArgs()[0])
		) {
			$constantStringTypes = $rightType->getConstantStrings();
			if (count($constantStringTypes) === 1 && $this->reflectionProvider->hasClass($constantStringTypes[0]->getValue())) {
				return $this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					new ObjectType($constantStringTypes[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStringTypes[0]->getValue())->asFinal()),
					$context,
					$scope,
				)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
			if ($rightType->getClassStringObjectType()->isObject()->yes()) {
				return $this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					$rightType->getClassStringObjectType(),
					$context,
					$scope,
				)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
		}

		if (
			$context->truthy()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& in_array(strtolower($unwrappedLeftExpr->name->toString()), [
				'substr', 'strstr', 'stristr', 'strchr', 'strrchr', 'strtolower', 'strtoupper', 'ucfirst', 'lcfirst',
				'mb_substr', 'mb_strstr', 'mb_stristr', 'mb_strchr', 'mb_strrchr', 'mb_strtolower', 'mb_strtoupper', 'mb_ucfirst', 'mb_lcfirst',
				'ucwords', 'mb_convert_case', 'mb_convert_kana',
			], true)
			&& isset($unwrappedLeftExpr->getArgs()[0])
			&& $rightType->isNonEmptyString()->yes()
		) {
			$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);

			if ($argType->isString()->yes()) {
				$specifiedTypes = new SpecifiedTypes();
				if (in_array(strtolower($unwrappedLeftExpr->name->toString()), ['strtolower', 'mb_strtolower'], true)) {
					$specifiedTypes = $this->create(
						$unwrappedRightExpr,
						TypeCombinator::intersect($rightType, new AccessoryLowercaseStringType()),
						$context,
						$scope,
					)->setRootExpr($expr);
				}
				if (in_array(strtolower($unwrappedLeftExpr->name->toString()), ['strtoupper', 'mb_strtoupper'], true)) {
					$specifiedTypes = $this->create(
						$unwrappedRightExpr,
						TypeCombinator::intersect($rightType, new AccessoryUppercaseStringType()),
						$context,
						$scope,
					)->setRootExpr($expr);
				}

				if ($rightType->isNonFalsyString()->yes()) {
					return $specifiedTypes->unionWith($this->create(
						$unwrappedLeftExpr->getArgs()[0]->value,
						TypeCombinator::intersect($argType, new AccessoryNonFalsyStringType()),
						$context,
						$scope,
					)->setRootExpr($expr));
				}

				return $specifiedTypes->unionWith($this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					TypeCombinator::intersect($argType, new AccessoryNonEmptyStringType()),
					$context,
					$scope,
				)->setRootExpr($expr));
			}
		}

		if ($rightType->isString()->yes()) {
			$types = null;
			foreach ($rightType->getConstantStrings() as $constantString) {
				$specifiedType = $this->specifyTypesForConstantStringBinaryExpression($unwrappedLeftExpr, $constantString, $context, $scope, $expr);

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
					$types = $types->unionWith($this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr));
				}
				return $types;
			}
		}

		$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
		if ($expressions !== null) {
			$exprNode = $expressions[0];
			$constantType = $expressions[1];

			$unwrappedExprNode = $exprNode;
			if ($exprNode instanceof AlwaysRememberedExpr) {
				$unwrappedExprNode = $exprNode->getExpr();
			}

			$specifiedType = $this->specifyTypesForConstantBinaryExpression($unwrappedExprNode, $constantType, $context, $scope, $expr);
			if ($specifiedType !== null) {
				if ($exprNode !== $unwrappedExprNode) {
					$specifiedType = $specifiedType->unionWith(
						$this->create($exprNode, $constantType, $context, $scope)->setRootExpr($expr),
					);
				}
				return $specifiedType;
			}
		}

		// $a::class === 'Foo'
		if (
			$context->true() &&
			$unwrappedLeftExpr instanceof ClassConstFetch &&
			$unwrappedLeftExpr->class instanceof Expr &&
			$unwrappedLeftExpr->name instanceof Node\Identifier &&
			$unwrappedRightExpr instanceof ClassConstFetch &&
			strtolower($unwrappedLeftExpr->name->toString()) === 'class'
		) {
			$constantStrings = $rightType->getConstantStrings();
			if (count($constantStrings) === 1 && $constantStrings[0]->getValue() !== '') {
				if ($this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
					return $this->create(
						$unwrappedLeftExpr->class,
						new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
						$context,
						$scope,
					)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
				}
				return $this->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$unwrappedLeftExpr->class,
						new Name($constantStrings[0]->getValue()),
					),
					$context,
				)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
		}

		$leftType = $scope->getType($leftExpr);

		// 'Foo' === $a::class
		if (
			$context->true() &&
			$unwrappedRightExpr instanceof ClassConstFetch &&
			$unwrappedRightExpr->class instanceof Expr &&
			$unwrappedRightExpr->name instanceof Node\Identifier &&
			$unwrappedLeftExpr instanceof ClassConstFetch &&
			strtolower($unwrappedRightExpr->name->toString()) === 'class'
		) {
			$constantStrings = $leftType->getConstantStrings();
			if (count($constantStrings) === 1 && $constantStrings[0]->getValue() !== '') {
				if ($this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
					return $this->create(
						$unwrappedRightExpr->class,
						new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
						$context,
						$scope,
					)->unionWith($this->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr));
				}

				return $this->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$unwrappedRightExpr->class,
						new Name($constantStrings[0]->getValue()),
					),
					$context,
				)->unionWith($this->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr));
			}
		}

		if ($context->false()) {
			$identicalType = $scope->getType($expr);
			if ($identicalType instanceof ConstantBooleanType) {
				$never = new NeverType();
				$contextForTypes = $identicalType->getValue() ? $context->negate() : $context;
				if ($leftExpr instanceof AlwaysRememberedExpr) {
					$leftTypes = $this->create($unwrappedLeftExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				} else {
					$leftTypes = $this->create($leftExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				}
				if ($rightExpr instanceof AlwaysRememberedExpr) {
					$rightTypes = $this->create($unwrappedRightExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				} else {
					$rightTypes = $this->create($rightExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				}
				return $leftTypes->unionWith($rightTypes);
			}
		}

		$types = null;
		if (
			count($leftType->getFiniteTypes()) === 1
			|| (
				$context->true()
				&& $leftType->isConstantValue()->yes()
				&& !$rightType->equals($leftType)
				&& $rightType->isSuperTypeOf($leftType)->yes())
		) {
			$types = $this->create(
				$rightExpr,
				$leftType,
				$context,
				$scope,
			)->setRootExpr($expr);
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$types = $types->unionWith($this->create(
					$unwrappedRightExpr,
					$leftType,
					$context,
					$scope,
				))->setRootExpr($expr);
			}
		}
		if (
			count($rightType->getFiniteTypes()) === 1
			|| (
				$context->true()
				&& $rightType->isConstantValue()->yes()
				&& !$leftType->equals($rightType)
				&& $leftType->isSuperTypeOf($rightType)->yes()
			)
		) {
			$leftTypes = $this->create(
				$leftExpr,
				$rightType,
				$context,
				$scope,
			)->setRootExpr($expr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith($this->create(
					$unwrappedLeftExpr,
					$rightType,
					$context,
					$scope,
				))->setRootExpr($expr);
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
				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			}
		}

		if ($context->true()) {
			$leftTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
			$rightTypes = $this->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith(
					$this->create($unwrappedLeftExpr, $rightType, $context, $scope)->setRootExpr($expr),
				);
			}
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$rightTypes = $rightTypes->unionWith(
					$this->create($unwrappedRightExpr, $leftType, $context, $scope)->setRootExpr($expr),
				);
			}
			return $leftTypes->unionWith($rightTypes);
		} elseif ($context->false()) {
			return $this->create($leftExpr, $leftType, $context, $scope)->setRootExpr($expr)->normalize($scope)
				->intersectWith($this->create($rightExpr, $rightType, $context, $scope)->setRootExpr($expr)->normalize($scope));
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
