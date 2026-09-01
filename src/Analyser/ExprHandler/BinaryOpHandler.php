<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Countable;
use DivisionByZeroError;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\CountNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_merge;
use function count;
use function get_class;
use function in_array;
use function is_string;
use function spl_object_id;
use function sprintf;
use function strtolower;

/**
 * @implements ExprHandler<BinaryOp>
 */
#[AutowiredService]
final class BinaryOpHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
		private PhpVersion $phpVersion,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private ExprPrinter $exprPrinter,
		private IdenticalNarrowingHelper $identicalNarrowingHelper,
		private CountNarrowingHelper $countNarrowingHelper,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BinaryOp
			&& !$expr instanceof BooleanAnd
			&& !$expr instanceof BinaryOp\LogicalAnd
			&& !$expr instanceof BooleanOr
			&& !$expr instanceof BinaryOp\LogicalOr
			&& !$expr instanceof BinaryOp\Coalesce
			&& !$expr instanceof BinaryOp\Pipe;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftResult->getScope(), $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
		$throwPoints = array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints());
		$impurePoints = array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints());
		if (
			($expr instanceof BinaryOp\Div || $expr instanceof BinaryOp\Mod) &&
			// the right operand was just processed on $leftResult's scope; read its
			// result instead of re-walking via Scope::getType().
			!$rightResult->getType()->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($leftResult->getScope(), new ObjectType(DivisionByZeroError::class), $expr, false);
		}
		if ($expr instanceof BinaryOp\Concat) {
			$leftToStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->left, $scope, $leftResult);
			$rightToStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->right, $leftResult->getScope(), $rightResult);
			$throwPoints = array_merge($throwPoints, $leftToStringResult->getThrowPoints(), $rightToStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $leftToStringResult->getImpurePoints(), $rightToStringResult->getImpurePoints());
		}
		$scope = $rightResult->getScope();

		$leftArgResult = $this->identicalNarrowingHelper->captureFirstArgResult($expr->left, $storage);
		$rightArgResult = $this->identicalNarrowingHelper->captureFirstArgResult($expr->right, $storage);
		// the comparison specify logic reads these operand subexpressions (count()
		// arguments, subtraction operands) - capture their walk results now: the
		// callback must not capture the storage itself (the storage holds the
		// results and the results hold their callbacks - a cycle the disabled GC
		// never collects)
		$specifySubResults = [];
		$specifySubExprs = [];
		if ($expr->right instanceof Expr\FuncCall && !$expr->right->isFirstClassCallable() && isset($expr->right->getArgs()[0])) {
			$specifySubExprs[] = $expr->right->getArgs()[0]->value;
		} elseif ($expr->right instanceof BinaryOp\Minus) {
			$specifySubExprs[] = $expr->right->right;
			if ($expr->right->left instanceof Expr\FuncCall && !$expr->right->left->isFirstClassCallable() && isset($expr->right->left->getArgs()[0])) {
				$specifySubExprs[] = $expr->right->left->getArgs()[0]->value;
			}
		}
		foreach ($specifySubExprs as $specifySubExpr) {
			$specifySubResult = $storage->findExpressionResult($specifySubExpr);
			if ($specifySubResult === null) {
				continue;
			}

			$specifySubResults[spl_object_id($specifySubExpr)] = $specifySubResult;
		}

		$typeCallback = function (bool $nativeTypesPromoted) use ($expr, $leftResult, $rightResult, $nodeScopeResolver, $beforeScope): Type {
			// the comparison helpers (resolveEqualType / RicherScopeGetTypeHelper)
			// read the operand types off the evaluation scope - native-promote it
			// here so the native flavour is honoured.
			$scope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
			// the operands were processed during processExpr; read their already
			// computed results instead of re-walking via Scope::getType().
			// Synthetic nodes the resolver builds (e.g. getDivType's Mod) are
			// priced on demand by the same helper.
			$getType = static function (Expr $e) use ($expr, $leftResult, $rightResult, $nativeTypesPromoted, $beforeScope, $nodeScopeResolver): Type {
				// getTypeOnScope re-prices narrowable operands against this
				// result's OWN beforeScope: for the main walk that is the walk
				// position (identical to getType()), but for an on-demand walk
				// of a synthetic (a rule asking about Identical($x, ...) on an
				// arm-narrowed scope) it is the asking scope, whose tracked
				// narrowing the stored operand results predate
				$flavouredScope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				// operands are re-priced from this result's own beforeScope:
				// for the main walk that is the walk position, but for an
				// on-demand walk of a synthetic (a rule asking about
				// Identical($x, ...) on an arm-narrowed scope) it carries
				// narrowing the stored operand results predate
				if ($e === $expr->left) {
					return $leftResult->getTypeOnScope($flavouredScope, $nativeTypesPromoted);
				}
				if ($e === $expr->right) {
					return $rightResult->getTypeOnScope($flavouredScope, $nativeTypesPromoted);
				}

				// InitializerExprTypeResolver also asks about synthetic composed
				// nodes (e.g. Mod($left, $right) for the int-division check) -
				// price those
				return $nodeScopeResolver->processSyntheticOnDemand($e, $flavouredScope)->getTypeOnScope($flavouredScope, $flavouredScope->nativeTypesPromoted);
			};

			if ($expr instanceof BinaryOp\Smaller) {
				return $getType($expr->left)->isSmallerThan($getType($expr->right), $this->phpVersion)->toBooleanType();
			}

			if ($expr instanceof BinaryOp\SmallerOrEqual) {
				return $getType($expr->left)->isSmallerThanOrEqual($getType($expr->right), $this->phpVersion)->toBooleanType();
			}

			if ($expr instanceof BinaryOp\Greater) {
				return $getType($expr->right)->isSmallerThan($getType($expr->left), $this->phpVersion)->toBooleanType();
			}

			if ($expr instanceof BinaryOp\GreaterOrEqual) {
				return $getType($expr->right)->isSmallerThanOrEqual($getType($expr->left), $this->phpVersion)->toBooleanType();
			}

			if ($expr instanceof BinaryOp\Equal) {
				return $this->resolveEqualType($scope, $expr, $leftResult, $rightResult);
			}

			if ($expr instanceof BinaryOp\NotEqual) {
				// negation of the Equal result - direct computation avoids
				// synthesizing a BooleanNot node (which would route through
				// on-demand re-processing once BooleanNot is migrated)
				$equalType = $this->resolveEqualType($scope, new BinaryOp\Equal($expr->left, $expr->right), $leftResult, $rightResult)->toBoolean();
				if ($equalType->isTrue()->yes()) {
					return new ConstantBooleanType(false);
				}
				if ($equalType->isFalse()->yes()) {
					return new ConstantBooleanType(true);
				}

				return new BooleanType();
			}

			if ($expr instanceof BinaryOp\Identical) {
				return $this->richerScopeGetTypeHelper->getIdenticalResult($scope, $expr, $nodeScopeResolver, $getType($expr->left), $getType($expr->right))->type;
			}

			if ($expr instanceof BinaryOp\NotIdentical) {
				return $this->richerScopeGetTypeHelper->getNotIdenticalResult($scope, $expr, $nodeScopeResolver, $getType($expr->left), $getType($expr->right))->type;
			}

			if ($expr instanceof BinaryOp\LogicalXor) {
				$leftBooleanType = $getType($expr->left)->toBoolean();
				$rightBooleanType = $getType($expr->right)->toBoolean();

				if (
					$leftBooleanType instanceof ConstantBooleanType
					&& $rightBooleanType instanceof ConstantBooleanType
				) {
					return new ConstantBooleanType(
						$leftBooleanType->getValue() xor $rightBooleanType->getValue(),
					);
				}

				return new BooleanType();
			}

			if ($expr instanceof BinaryOp\Spaceship) {
				return $this->initializerExprTypeResolver->getSpaceshipType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Concat) {
				return $this->initializerExprTypeResolver->getConcatType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\BitwiseAnd) {
				return $this->initializerExprTypeResolver->getBitwiseAndType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\BitwiseOr) {
				return $this->initializerExprTypeResolver->getBitwiseOrType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\BitwiseXor) {
				return $this->initializerExprTypeResolver->getBitwiseXorType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Div) {
				return $this->initializerExprTypeResolver->getDivType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Mod) {
				return $this->initializerExprTypeResolver->getModType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Plus) {
				return $this->initializerExprTypeResolver->getPlusType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Minus) {
				return $this->initializerExprTypeResolver->getMinusType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Mul) {
				return $this->initializerExprTypeResolver->getMulType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\Pow) {
				return $this->initializerExprTypeResolver->getPowType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\ShiftLeft) {
				return $this->initializerExprTypeResolver->getShiftLeftType($expr->left, $expr->right, $getType);
			}

			if ($expr instanceof BinaryOp\ShiftRight) {
				return $this->initializerExprTypeResolver->getShiftRightType($expr->left, $expr->right, $getType);
			}

			throw new ShouldNotHappenException(sprintf('Unhandled %s', get_class($expr)));
		};

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating() || $rightResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: $typeCallback,
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $leftResult, $rightResult, $nodeScopeResolver, $beforeScope, $specifySubResults, $leftArgResult, $rightArgResult, $typeCallback): SpecifiedTypes {
				$scope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				if ($expr instanceof BinaryOp\Identical || $expr instanceof BinaryOp\NotIdentical) {
					// `!==` narrowing is the `===` narrowing in the negated context -
					// no synthetic Identical node. A null context never negates.
					if ($context->null() && $expr instanceof BinaryOp\NotIdentical) {
						return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
					}

					$newWorldTypes = $this->identicalNarrowingHelper->specifyIdentical(
						$nodeScopeResolver,
						$expr->left,
						$expr->right,
						$leftResult,
						$rightResult,
						$expr instanceof BinaryOp\NotIdentical ? $context->negate() : $context,
						// the narrowing composes on the evaluation scope; only the
						// asked flavour comes from the asking scope
						$scope,
						$leftArgResult,
						$rightArgResult,
						// the comparison's own verdict, in Identical semantics -
						// computed from the captured operand results (the walk's
						// evaluation point), only the flavour follows the ask
						static function () use ($expr, $nativeTypesPromoted, $typeCallback): Type {
							$ownType = $typeCallback($nativeTypesPromoted);
							if ($expr instanceof BinaryOp\NotIdentical) {
								if ($ownType->isTrue()->yes()) {
									return new ConstantBooleanType(false);
								}
								if ($ownType->isFalse()->yes()) {
									return new ConstantBooleanType(true);
								}
							}

							return $ownType;
						},
					);

					// null = no shape-specific narrowing (unknown-class ::class,
					// null-context asks) - the default is all that remains
					return ($newWorldTypes ?? $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context))->setRootExpr($expr);
				}

				if ($expr instanceof BinaryOp\Equal || $expr instanceof BinaryOp\NotEqual) {
					// `!=` narrowing is the `==` narrowing in the negated context
					if ($context->null() && $expr instanceof BinaryOp\NotEqual) {
						return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
					}

					$newWorldTypes = $this->identicalNarrowingHelper->specifyEqual(
						$nodeScopeResolver,
						$expr->left,
						$expr->right,
						$leftResult,
						$rightResult,
						$expr instanceof BinaryOp\NotEqual ? $context->negate() : $context,
						$scope,
						$leftArgResult,
						$rightArgResult,
					);

					return ($newWorldTypes ?? $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context))->setRootExpr($expr);
				}

				if ($expr instanceof BinaryOp\Smaller || $expr instanceof BinaryOp\SmallerOrEqual) {
					if (
						$expr->left instanceof Expr\FuncCall
						&& $expr->left->name instanceof Name
						&& !$expr->left->isFirstClassCallable()
						&& in_array(strtolower((string) $expr->left->name), ['count', 'sizeof', 'strlen', 'mb_strlen', 'preg_match'], true)
						&& count($expr->left->getArgs()) >= 1
						&& (
							!$expr->right instanceof Expr\FuncCall
							|| !$expr->right->name instanceof Name
							|| !in_array(strtolower((string) $expr->right->name), ['count', 'sizeof', 'strlen', 'mb_strlen', 'preg_match'], true)
						)
					) {
						$inverseOperator = $expr instanceof BinaryOp\Smaller
							? new BinaryOp\SmallerOrEqual($expr->right, $expr->left)
							: new BinaryOp\Smaller($expr->right, $expr->left);

						// negating the context is exactly what a BooleanNot around the
						// inverse operator would do - direct computation avoids
						// synthesizing a BooleanNot node. A null context never negates
						// (BooleanNot defaults on it too).
						if ($context->null()) {
							return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
						}

						return $this->defaultNarrowingHelper->specifyTypesForNode(
							$scope,
							$inverseOperator,
							$context->negate(),
						)->setRootExpr($expr);
					}

					$orEqual = $expr instanceof BinaryOp\SmallerOrEqual;
					$offset = $orEqual ? 0 : 1;
					// the operands were processed during processExpr; read their
					// already computed results instead of re-walking via
					// Scope::getType(). Their subexpressions (e.g. count() arguments)
					// were also processed and are read from the stored result.
					$getType = static function (Expr $e) use ($expr, $leftResult, $rightResult, $scope, $specifySubResults, $nativeTypesPromoted): Type {
						if ($e === $expr->left) {
							return $nativeTypesPromoted ? $leftResult->getNativeType() : $leftResult->getType();
						}
						if ($e === $expr->right) {
							return $nativeTypesPromoted ? $rightResult->getNativeType() : $rightResult->getType();
						}

						// the remaining asks are operand subexpressions whose walk
						// results were captured at creation
						$result = $specifySubResults[spl_object_id($e)] ?? null;
						if ($result === null) {
							throw new ShouldNotHappenException();
						}

						return $result->getTypeOnScope($scope, $scope->nativeTypesPromoted);
					};
					$leftType = $getType($expr->left);
					$result = (new SpecifiedTypes([], []))->setRootExpr($expr);

					if (
						!$context->null()
						&& $expr->right instanceof Expr\FuncCall
						&& $expr->right->name instanceof Name
						&& !$expr->right->isFirstClassCallable()
						&& in_array(strtolower((string) $expr->right->name), ['count', 'sizeof'], true)
						&& count($expr->right->getArgs()) >= 1
						&& $leftType->isInteger()->yes()
					) {
						$argType = $getType($expr->right->getArgs()[0]->value);

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
							$specifiedTypes = $this->countNarrowingHelper->specifyCountSize($expr->right, $argType, $sizeType, $context, $scope, $expr);
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

									return $this->defaultNarrowingHelper->createForSubject($expr->right->getArgs()[0]->value, $countableType, $context, $scope)->setRootExpr($expr);
								}
							}

							if ($argType->isArray()->yes()) {
								$newType = new NonEmptyArrayType();
								if ($context->true() && $argType->isList()->yes()) {
									$newType = TypeCombinator::intersect($newType, new AccessoryArrayListType());
								}

								$result = $result->unionWith(
									$this->defaultNarrowingHelper->createForSubject($expr->right->getArgs()[0]->value, $newType, $context, $scope)->setRootExpr($expr),
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
							$dimFetch = new Expr\ArrayDimFetch($arrayArg, $expr->left);
							$result = $result->unionWith(
								$this->defaultNarrowingHelper->createForSubject($dimFetch, $argType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope)->setRootExpr($expr),
							);
						}
					}

					// infer $list[$index] after $zeroOrMore < count($list) - N
					// infer $list[$index] after $zeroOrMore <= count($list) - N
					if (
						$context->true()
						&& $expr->right instanceof BinaryOp\Minus
						&& $expr->right->left instanceof Expr\FuncCall
						&& $expr->right->left->name instanceof Name
						&& !$expr->right->left->isFirstClassCallable()
						&& in_array(strtolower((string) $expr->right->left->name), ['count', 'sizeof'], true)
						&& count($expr->right->left->getArgs()) >= 1
						// constant offsets are handled via HasOffsetType/HasOffsetValueType
						&& !$leftType instanceof ConstantIntegerType
						&& $leftType->isInteger()->yes()
						&& IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($leftType)->yes()
					) {
						$countArgType = $getType($expr->right->left->getArgs()[0]->value);
						$subtractedType = $getType($expr->right->right);
						if (
							$countArgType->isList()->yes()
							&& $this->countNarrowingHelper->isNormalCountCall($expr->right->left, $countArgType, $scope)->yes()
							&& IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($subtractedType)->yes()
						) {
							$arrayArg = $expr->right->left->getArgs()[0]->value;
							$dimFetch = new Expr\ArrayDimFetch($arrayArg, $expr->left);
							$result = $result->unionWith(
								$this->defaultNarrowingHelper->createForSubject($dimFetch, $countArgType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope)->setRootExpr($expr),
							);
						}
					}

					if (
						!$context->null()
						&& $expr->right instanceof Expr\FuncCall
						&& $expr->right->name instanceof Name
						&& !$expr->right->isFirstClassCallable()
						&& in_array(strtolower((string) $expr->right->name), ['preg_match'], true)
						&& count($expr->right->getArgs()) >= 3
						&& (
							IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($leftType)->yes()
							|| ($expr instanceof BinaryOp\Smaller && IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($leftType)->yes())
						)
					) {
						// 0 < preg_match or 1 <= preg_match becomes 1 === preg_match
						$newExpr = new BinaryOp\Identical($expr->right, new Scalar\Int_(1));

						return $this->defaultNarrowingHelper->specifyTypesForNode($scope, $newExpr, $context)->setRootExpr($expr);
					}

					if (
						!$context->null()
						&& $expr->right instanceof Expr\FuncCall
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
							$argType = $getType($expr->right->getArgs()[0]->value);
							if ($argType->isString()->yes()) {
								$accessory = new AccessoryNonEmptyStringType();

								if (IntegerRangeType::createAllGreaterThanOrEqualTo(2 - $offset)->isSuperTypeOf($leftType)->yes()) {
									$accessory = new AccessoryNonFalsyStringType();
								}

								$result = $result->unionWith($this->defaultNarrowingHelper->createForSubject($expr->right->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($expr));
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

					$rightType = $getType($expr->right);
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
						if (!$expr->left instanceof Scalar && !($expr->left instanceof Expr\UnaryMinus && $expr->left->expr instanceof Scalar)) {
							$result = $result->unionWith(
								$this->defaultNarrowingHelper->createForSubject(
									$expr->left,
									$orEqual ? $rightType->getSmallerOrEqualType($this->phpVersion) : $rightType->getSmallerType($this->phpVersion),
									TypeSpecifierContext::createTruthy(),
									$scope,
								)->setRootExpr($expr),
							);
						}
						if (!$expr->right instanceof Scalar && !($expr->right instanceof Expr\UnaryMinus && $expr->right->expr instanceof Scalar)) {
							$result = $result->unionWith(
								$this->defaultNarrowingHelper->createForSubject(
									$expr->right,
									$orEqual ? $leftType->getGreaterOrEqualType($this->phpVersion) : $leftType->getGreaterType($this->phpVersion),
									TypeSpecifierContext::createTruthy(),
									$scope,
								)->setRootExpr($expr),
							);
						}
					} elseif ($context->false()) {
						if (!$expr->left instanceof Scalar && !($expr->left instanceof Expr\UnaryMinus && $expr->left->expr instanceof Scalar)) {
							$result = $result->unionWith(
								$this->defaultNarrowingHelper->createForSubject(
									$expr->left,
									$orEqual ? $rightType->getGreaterType($this->phpVersion) : $rightType->getGreaterOrEqualType($this->phpVersion),
									TypeSpecifierContext::createTruthy(),
									$scope,
								)->setRootExpr($expr),
							);
						}
						if (!$expr->right instanceof Scalar && !($expr->right instanceof Expr\UnaryMinus && $expr->right->expr instanceof Scalar)) {
							$result = $result->unionWith(
								$this->defaultNarrowingHelper->createForSubject(
									$expr->right,
									$orEqual ? $leftType->getSmallerType($this->phpVersion) : $leftType->getSmallerOrEqualType($this->phpVersion),
									TypeSpecifierContext::createTruthy(),
									$scope,
								)->setRootExpr($expr),
							);
						}
					}

					return $result;
				}

				if ($expr instanceof BinaryOp\Greater) {
					return $this->defaultNarrowingHelper->specifyTypesForNode($scope, new BinaryOp\Smaller($expr->right, $expr->left), $context)->setRootExpr($expr);
				}

				if ($expr instanceof BinaryOp\GreaterOrEqual) {
					return $this->defaultNarrowingHelper->specifyTypesForNode($scope, new BinaryOp\SmallerOrEqual($expr->right, $expr->left), $context)->setRootExpr($expr);
				}

				return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
			},
		);
	}

	/**
	 * The boolean result of a `==` comparison, including the same-variable
	 * special case. Shared by the Equal and NotEqual type callbacks.
	 */
	private function resolveEqualType(MutatingScope $scope, BinaryOp\Equal $expr, ExpressionResult $leftResult, ExpressionResult $rightResult): Type
	{
		if (
			$expr->left instanceof Variable
			&& is_string($expr->left->name)
			&& $expr->right instanceof Variable
			&& is_string($expr->right->name)
			&& $expr->left->name === $expr->right->name
		) {
			return new ConstantBooleanType(true);
		}

		// the operands were processed during processExpr; use their results' types.
		$leftType = $leftResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		$rightType = $rightResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);

		return $this->initializerExprTypeResolver->resolveEqualType($leftType, $rightType)->type;
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

}
