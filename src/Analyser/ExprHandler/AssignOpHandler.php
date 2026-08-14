<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use DivisionByZeroError;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\AssignTargetWalkMode;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\CoalesceCompositionHelper;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\CoalesceExpressionNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use function array_merge;
use function get_class;
use function is_string;
use function sprintf;

/**
 * @implements ExprHandler<AssignOp>
 */
#[AutowiredService]
final class AssignOpHandler implements ExprHandler
{

	public function __construct(
		private AssignHandler $assignHandler,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private CoalesceCompositionHelper $coalesceCompositionHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof AssignOp;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;

		$target = $this->assignHandler->prepareTarget(
			$nodeScopeResolver,
			$scope,
			$storage,
			$stmt,
			$expr->var,
			$expr,
			$nodeCallback,
			$context,
			$expr instanceof Expr\AssignOp\Coalesce ? AssignTargetWalkMode::coalesceReadModifyWrite() : AssignTargetWalkMode::readModifyWrite(),
		);
		$targetReadResult = $target->getTargetReadResult();
		$condResult = $expr instanceof Expr\AssignOp\Coalesce ? $targetReadResult : null;
		$chainResults = $target->getTargetChainResults();

		$rightResult = null;
		$valueBeforeScope = $target->getScope();
		$valueScope = $valueBeforeScope;
		$valueContext = $context;
		if ($expr instanceof Expr\AssignOp\Coalesce) {
			if ($condResult === null) {
				throw new ShouldNotHappenException();
			}

			// the value expr only evaluates when the left side is null or
			// unset - the falsey isset() narrowing, composed from the left read
			$valueScope = $valueScope->applySpecifiedTypes($this->coalesceCompositionHelper->getRightSideScopeSpecifiedTypes($valueScope, $expr->var, $condResult, $chainResults, $expr));

			if ($expr->var instanceof Expr\Variable && is_string($expr->var->name)) {
				$valueContext = $valueContext->enterRightSideAssign(
					$expr->var->name,
					$expr->expr,
				);
			}
		}

		$valueResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $valueScope, $storage, $nodeCallback, $valueContext->enterDeep());
		$rhsResult = $valueResult;
		if ($expr instanceof Expr\AssignOp\Coalesce) {
			$rightResult = $valueResult;
			$valueResult = $this->expressionResultFactory->create(
				$rightResult->getScope()->mergeWith($valueBeforeScope),
				$valueBeforeScope,
				$expr->expr,
				$rightResult->hasYield(),
				$rightResult->isAlwaysTerminating() && $condResult->getType()->isNull()->yes(),
				$rightResult->getThrowPoints(),
				$rightResult->getImpurePoints(),
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			);
		}

		$typeCallback = function (bool $nativeTypesPromoted) use ($expr, $nodeScopeResolver, $beforeScope, $condResult, $chainResults, $rightResult, $targetReadResult, $rhsResult): Type {
			// the operands' results are in hand: the target read from
			// prepareTarget(), the value expr from the phase between
			// prepareTarget() and applyWrite() - no storage round-trip
			$getType = static function (Expr $e) use ($expr, $nodeScopeResolver, $beforeScope, $targetReadResult, $rhsResult, $nativeTypesPromoted): Type {
				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				if ($e === $expr->var) {
					return $targetReadResult->getTypeOnScope($s, $s->nativeTypesPromoted);
				}
				if ($e === $expr->expr) {
					return $rhsResult->getTypeOnScope($s, $s->nativeTypesPromoted);
				}

				// InitializerExprTypeResolver also asks about synthetic composed
				// nodes (e.g. Mod($left, $right) for modulo bounds) - price those
				return $nodeScopeResolver->processSyntheticOnDemand($e, $s)->getTypeOnScope($s, $s->nativeTypesPromoted);
			};

			if ($expr instanceof Expr\AssignOp\Coalesce) {
				return $this->coalesceCompositionHelper->composeType(
					$nodeScopeResolver,
					$expr->var,
					$condResult,
					$rightResult,
					$beforeScope,
					$chainResults,
					$expr,
					$nativeTypesPromoted,
				);
			}

			if ($expr instanceof Expr\AssignOp\Concat) {
				return $this->initializerExprTypeResolver->getConcatType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\BitwiseAnd) {
				return $this->initializerExprTypeResolver->getBitwiseAndType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\BitwiseOr) {
				return $this->initializerExprTypeResolver->getBitwiseOrType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\BitwiseXor) {
				return $this->initializerExprTypeResolver->getBitwiseXorType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\Div) {
				return $this->initializerExprTypeResolver->getDivType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\Mod) {
				return $this->initializerExprTypeResolver->getModType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\Plus) {
				return $this->initializerExprTypeResolver->getPlusType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\Minus) {
				return $this->initializerExprTypeResolver->getMinusType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\Mul) {
				return $this->initializerExprTypeResolver->getMulType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\Pow) {
				return $this->initializerExprTypeResolver->getPowType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\ShiftLeft) {
				return $this->initializerExprTypeResolver->getShiftLeftType($expr->var, $expr->expr, $getType);
			}

			if ($expr instanceof Expr\AssignOp\ShiftRight) {
				return $this->initializerExprTypeResolver->getShiftRightType($expr->var, $expr->expr, $getType);
			}

			throw new ShouldNotHappenException(sprintf('Unhandled %s', get_class($expr)));
		};
		$specifyTypesCallback = function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $condResult, $beforeScope): SpecifiedTypes {
			$types = $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
			if (!$expr instanceof Expr\AssignOp\Coalesce || $context->null()) {
				return $types;
			}

			// a truthiness constraint on `$x ??= y` also constrains the assigned
			// target - the specify-side mirror of the createTypesCallback below
			// (the raw term on the assign node itself cannot be unpacked at the
			// application point)
			if (!$context->truthy()) {
				$removedType = StaticTypeFactory::truthy();
			} elseif (!$context->falsey()) {
				$removedType = StaticTypeFactory::falsey();
			} else {
				return $types;
			}
			$cs = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;

			return $types->unionWith($this->defaultNarrowingHelper->createSubjectTypes($cs, $expr->var, $condResult, $removedType, TypeSpecifierContext::createFalse())->setRootExpr($expr));
		};
		$createTypesCallback = null;
		if ($expr instanceof Expr\AssignOp\Coalesce) {
			// a type constraint on `$x ??= y` constrains the assigned variable -
			// what TypeSpecifier::create() recovered by its AssignOp\Coalesce arm
			$createTypesCallback = function (Type $constraintType, TypeSpecifierContext $cctx, bool $nativeTypesPromoted) use ($expr, $condResult, $beforeScope): SpecifiedTypes {
				$cs = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;

				return $this->defaultNarrowingHelper->createSubjectTypes($cs, $expr->var, $condResult, $constraintType, $cctx);
			};
		}

		// the result standing for the whole `$lvalue OP= value` expression - the
		// value applyWrite() writes to the target
		$assignOpValueResult = $this->expressionResultFactory->create(
			$beforeScope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
			createTypesCallback: $createTypesCallback,
		);

		$assignResult = $this->assignHandler->applyWrite(
			$nodeScopeResolver,
			$target,
			$valueResult,
			$assignOpValueResult,
			$stmt,
			$storage,
			$nodeCallback,
			$context,
		);
		$scope = $assignResult->getScope();
		$throwPoints = $assignResult->getThrowPoints();
		$impurePoints = $assignResult->getImpurePoints();
		if (
			($expr instanceof Expr\AssignOp\Div || $expr instanceof Expr\AssignOp\Mod) &&
			!$rhsResult->getTypeOnScope($scope, false)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
		}
		if ($expr instanceof Expr\AssignOp\Concat) {
			$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $scope, $rhsResult);
			$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());
		}

		if ($expr instanceof Expr\AssignOp\Coalesce) {
			$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new CoalesceExpressionNode($expr, $condResult, 'on left side of ??='), $beforeScope, $storage, $context);
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $assignResult->hasYield(),
			isAlwaysTerminating: $assignResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
			createTypesCallback: $createTypesCallback,
		);
	}

}
