<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\CoalesceCompositionHelper;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\CoalesceExpressionNode;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Coalesce>
 */
#[AutowiredService]
final class CoalesceHandler implements ExprHandler
{

	public function __construct(
		private NonNullabilityHelper $nonNullabilityHelper,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private CoalesceCompositionHelper $coalesceCompositionHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Coalesce;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->left);
		$condScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->left);
		$condResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $condScope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $this->nonNullabilityHelper->revertNonNullability($condResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->left);

		// the falsey narrowing of this very node - asking the scope about it
		// mid-processing would take the on-demand path and recurse
		$rightScope = $scope->applySpecifiedTypes($this->coalesceCompositionHelper->getFalseySpecifiedTypes($scope, $scope, $expr->left, $condResult, $expr, TypeSpecifierContext::createFalsey()));
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $rightScope, $storage, $nodeCallback, $context->enterDeep());
		// the left-is-set narrowing, composed from the already-processed chain
		// results - the inside-out equivalent of narrowing by isset($expr->left)
		// without synthesizing an Isset_ node and re-walking the chain on demand
		$chainResults = [];
		$this->defaultNarrowingHelper->captureChainResults($expr->left, $storage, $chainResults);
		$leftIssetTypes = $this->defaultNarrowingHelper->createIssetTruthyChainTypes(
			$scope,
			$expr->left,
			$this->defaultNarrowingHelper->buildChainTypeReader($chainResults, $scope),
			$expr,
			TypeSpecifierContext::createTruthy(),
		);

		$rightExprType = $rightResult->getType();
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$scope = $scope->applySpecifiedTypes($leftIssetTypes);
		} else {
			$scope = $scope->applySpecifiedTypes($leftIssetTypes)->mergeWith($rightResult->getScope());
		}

		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new CoalesceExpressionNode($expr, $condResult, 'on left side of ??'), $beforeScope, $storage, $context);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $condResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $condResult->isAlwaysTerminating(),
			throwPoints: array_merge($condResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($condResult->getImpurePoints(), $rightResult->getImpurePoints()),
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->coalesceCompositionHelper->composeType(
				$nodeScopeResolver,
				$expr->left,
				$condResult,
				$rightResult,
				// the isset resolution and the left-is-set narrowing run on
				// beforeScope (the evaluation point), not the asking scope.
				$beforeScope,
				$chainResults,
				$expr,
				$nativeTypesPromoted,
			),
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $condResult, $rightResult, $beforeScope): SpecifiedTypes {
				if ($context->null()) {
					return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
				}

				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				if (!$context->true()) {
					return $this->coalesceCompositionHelper->getFalseySpecifiedTypes($s, $s, $expr->left, $condResult, $expr, $context);
				}

				if (
					!$context->falsey()
					&& (new ConstantBooleanType(false))->isSuperTypeOf(($nativeTypesPromoted ? $rightResult->getNativeType() : $rightResult->getType())->toBoolean())->yes()
				) {
					return $this->defaultNarrowingHelper->createSubjectTypes($s, $expr->left, $condResult, new NullType(), TypeSpecifierContext::createFalse())->setRootExpr($expr);
				}

				// The Coalesce condition matched but produced no narrowing; the legacy
				// if/elseif chain fell through to its empty-SpecifiedTypes tail here,
				// not to the truthy/falsey default.
				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			},
			// a type constraint on the coalesce constrains its left side when
			// the type rules the right side in or out - what
			// TypeSpecifier::create() recovered by unwrapping the coalesce
			createTypesCallback: function (Type $type, TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $condResult, $rightResult, $beforeScope): SpecifiedTypes {
				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				if (!$context->null()) {
					$rightType = $nativeTypesPromoted ? $rightResult->getNativeType() : $rightResult->getType();
					if (
						($context->true() && $type->isSuperTypeOf($rightType)->no())
						|| ($context->false() && $type->isSuperTypeOf($rightType)->yes())
					) {
						// the coalesce's own key is emitted alongside the left-side
						// narrowing (createForExpr's double-key, like the nullsafe
						// handlers) - consumers summing the checked expression's own
						// entry (ImpossibleCheckTypeHelper) rely on it
						return $this->defaultNarrowingHelper->createSubjectTypes($s, $expr->left, $condResult, $type, $context)
							->unionWith($this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $context));
					}
				}

				return $this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $context);
			},
		);
	}

}
