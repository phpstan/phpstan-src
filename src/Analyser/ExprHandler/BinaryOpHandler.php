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
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
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
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftResult->getScope(), $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints());
		$impurePoints = array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints());
		if (
			($expr instanceof BinaryOp\Div || $expr instanceof BinaryOp\Mod) &&
			!$leftResult->getScope()->getType($expr->right)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($leftResult->getScope(), new ObjectType(DivisionByZeroError::class), $expr, false);
		}
		if ($expr instanceof BinaryOp\Concat) {
			$leftToStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->left, $scope);
			$rightToStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->right, $leftResult->getScope());
			$throwPoints = array_merge($throwPoints, $leftToStringResult->getThrowPoints(), $rightToStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $leftToStringResult->getImpurePoints(), $rightToStringResult->getImpurePoints());
		}
		$scope = $rightResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating() || $rightResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$getType = static fn (Expr $expr): Type => $scope->getType($expr);

		if ($expr instanceof BinaryOp\Smaller) {
			return $scope->getType($expr->left)->isSmallerThan($scope->getType($expr->right), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\SmallerOrEqual) {
			return $scope->getType($expr->left)->isSmallerThanOrEqual($scope->getType($expr->right), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\Greater) {
			return $scope->getType($expr->right)->isSmallerThan($scope->getType($expr->left), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\GreaterOrEqual) {
			return $scope->getType($expr->right)->isSmallerThanOrEqual($scope->getType($expr->left), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\Equal) {
			if (
				$expr->left instanceof Variable
				&& is_string($expr->left->name)
				&& $expr->right instanceof Variable
				&& is_string($expr->right->name)
				&& $expr->left->name === $expr->right->name
			) {
				return new ConstantBooleanType(true);
			}

			$leftType = $scope->getType($expr->left);
			$rightType = $scope->getType($expr->right);

			return $this->initializerExprTypeResolver->resolveEqualType($leftType, $rightType)->type;
		}

		if ($expr instanceof BinaryOp\NotEqual) {
			return $scope->getType(new Expr\BooleanNot(new BinaryOp\Equal($expr->left, $expr->right)));
		}

		if ($expr instanceof BinaryOp\Identical) {
			return $this->richerScopeGetTypeHelper->getIdenticalResult($scope, $expr)->type;
		}

		if ($expr instanceof BinaryOp\NotIdentical) {
			return $this->richerScopeGetTypeHelper->getNotIdenticalResult($scope, $expr)->type;
		}

		if ($expr instanceof BinaryOp\LogicalXor) {
			$leftBooleanType = $scope->getType($expr->left)->toBoolean();
			$rightBooleanType = $scope->getType($expr->right)->toBoolean();

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
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($expr instanceof BinaryOp\Identical) {
			return $typeSpecifier->resolveIdentical($expr, $scope, $context);
		}

		if ($expr instanceof BinaryOp\NotIdentical) {
			return $typeSpecifier->specifyTypesInCondition(
				$scope,
				new Expr\BooleanNot(new BinaryOp\Identical($expr->left, $expr->right)),
				$context,
			)->setRootExpr($expr);
		}

		if ($expr instanceof BinaryOp\Equal) {
			return $typeSpecifier->resolveEqual($expr, $scope, $context);
		}

		if ($expr instanceof BinaryOp\NotEqual) {
			return $typeSpecifier->specifyTypesInCondition(
				$scope,
				new Expr\BooleanNot(new BinaryOp\Equal($expr->left, $expr->right)),
				$context,
			)->setRootExpr($expr);
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

				return $typeSpecifier->specifyTypesInCondition(
					$scope,
					new Expr\BooleanNot($inverseOperator),
					$context,
				)->setRootExpr($expr);
			}

			$orEqual = $expr instanceof BinaryOp\SmallerOrEqual;
			$offset = $orEqual ? 0 : 1;
			$leftType = $scope->getType($expr->left);
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
					$specifiedTypes = $typeSpecifier->specifyTypesForCountFuncCall($expr->right, $argType, $sizeType, $context, $scope, $expr);
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

							return $typeSpecifier->create($expr->right->getArgs()[0]->value, $countableType, $context, $scope)->setRootExpr($expr);
						}
					}

					if ($argType->isArray()->yes()) {
						$newType = new NonEmptyArrayType();
						if ($context->true() && $argType->isList()->yes()) {
							$newType = TypeCombinator::intersect($newType, new AccessoryArrayListType());
						}

						$result = $result->unionWith(
							$typeSpecifier->create($expr->right->getArgs()[0]->value, $newType, $context, $scope)->setRootExpr($expr),
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
						$typeSpecifier->create($dimFetch, $argType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope)->setRootExpr($expr),
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
				$countArgType = $scope->getType($expr->right->left->getArgs()[0]->value);
				$subtractedType = $scope->getType($expr->right->right);
				if (
					$countArgType->isList()->yes()
					&& $typeSpecifier->isNormalCountCall($expr->right->left, $countArgType, $scope)->yes()
					&& IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($subtractedType)->yes()
				) {
					$arrayArg = $expr->right->left->getArgs()[0]->value;
					$dimFetch = new Expr\ArrayDimFetch($arrayArg, $expr->left);
					$result = $result->unionWith(
						$typeSpecifier->create($dimFetch, $countArgType->getIterableValueType(), TypeSpecifierContext::createTrue(), $scope)->setRootExpr($expr),
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

				return $typeSpecifier->specifyTypesInCondition($scope, $newExpr, $context)->setRootExpr($expr);
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
					$argType = $scope->getType($expr->right->getArgs()[0]->value);
					if ($argType->isString()->yes()) {
						$accessory = new AccessoryNonEmptyStringType();

						if (IntegerRangeType::createAllGreaterThanOrEqualTo(2 - $offset)->isSuperTypeOf($leftType)->yes()) {
							$accessory = new AccessoryNonFalsyStringType();
						}

						$result = $result->unionWith($typeSpecifier->create($expr->right->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($expr));
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
				if (!$expr->left instanceof Scalar && !($expr->left instanceof Expr\UnaryMinus && $expr->left->expr instanceof Scalar)) {
					$result = $result->unionWith(
						$typeSpecifier->create(
							$expr->left,
							$orEqual ? $rightType->getSmallerOrEqualType($this->phpVersion) : $rightType->getSmallerType($this->phpVersion),
							TypeSpecifierContext::createTruthy(),
							$scope,
						)->setRootExpr($expr),
					);
				}
				if (!$expr->right instanceof Scalar && !($expr->right instanceof Expr\UnaryMinus && $expr->right->expr instanceof Scalar)) {
					$result = $result->unionWith(
						$typeSpecifier->create(
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
						$typeSpecifier->create(
							$expr->left,
							$orEqual ? $rightType->getGreaterType($this->phpVersion) : $rightType->getGreaterOrEqualType($this->phpVersion),
							TypeSpecifierContext::createTruthy(),
							$scope,
						)->setRootExpr($expr),
					);
				}
				if (!$expr->right instanceof Scalar && !($expr->right instanceof Expr\UnaryMinus && $expr->right->expr instanceof Scalar)) {
					$result = $result->unionWith(
						$typeSpecifier->create(
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
			return $typeSpecifier->specifyTypesInCondition($scope, new BinaryOp\Smaller($expr->right, $expr->left), $context)->setRootExpr($expr);
		}

		if ($expr instanceof BinaryOp\GreaterOrEqual) {
			return $typeSpecifier->specifyTypesInCondition($scope, new BinaryOp\SmallerOrEqual($expr->right, $expr->left), $context)->setRootExpr($expr);
		}

		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
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
