<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<Ternary>
 */
#[AutowiredService]
final class TernaryHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private TypeSpecifier $typeSpecifier,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Ternary;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$condResult = $this->nodeScopeResolver->processExprNode(new Stmt\Expression($expr->cond), $expr->cond, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
		if ($expr->if === null) {
			$conditionType = $scope->getType($expr->cond);
			$booleanConditionType = $conditionType->toBoolean();
			if ($booleanConditionType->isTrue()->yes()) {
				return $condResult->getTruthyScope()->getType($expr->cond);
			}

			if ($booleanConditionType->isFalse()->yes()) {
				return $condResult->getFalseyScope()->getType($expr->else);
			}

			return TypeCombinator::union(
				TypeCombinator::removeFalsey($condResult->getTruthyScope()->getType($expr->cond)),
				$condResult->getFalseyScope()->getType($expr->else),
			);
		}

		$booleanConditionType = $scope->getType($expr->cond)->toBoolean();
		if ($booleanConditionType->isTrue()->yes()) {
			return $condResult->getTruthyScope()->getType($expr->if);
		}

		if ($booleanConditionType->isFalse()->yes()) {
			return $condResult->getFalseyScope()->getType($expr->else);
		}

		return TypeCombinator::union(
			$condResult->getTruthyScope()->getType($expr->if),
			$condResult->getFalseyScope()->getType($expr->else),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($expr->cond instanceof Ternary || $context->null()) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

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

		return $typeSpecifier->specifyTypesInCondition($scope, $conditionExpr, $context)->setRootExpr($expr);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$ternaryCondResult = $nodeScopeResolver->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = $ternaryCondResult->getThrowPoints();
		$impurePoints = $ternaryCondResult->getImpurePoints();
		$ifTrueScope = $ternaryCondResult->getTruthyScope();
		$ifFalseScope = $ternaryCondResult->getFalseyScope();
		$ifTrueType = null;
		$ifResult = null;

		if ($expr->if === null) {
			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$ifFalseScope = $elseResult->getScope();
		} else {
			$ifResult = $nodeScopeResolver->processExprNode($stmt, $expr->if, $ifTrueScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $ifResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $ifResult->getImpurePoints());
			$ifTrueScope = $ifResult->getScope();
			$ifTrueType = $ifResult->getType();

			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$ifFalseScope = $elseResult->getScope();
		}

		$condType = $ternaryCondResult->getType();
		if ($condType->isTrue()->yes()) {
			$finalScope = $ifTrueScope;
		} elseif ($condType->isFalse()->yes()) {
			$finalScope = $ifFalseScope;
		} else {
			if ($ifTrueType instanceof NeverType && $ifTrueType->isExplicit()) {
				$finalScope = $ifFalseScope;
			} else {
				$ifFalseType = $elseResult->getType();

				if ($ifFalseType instanceof NeverType && $ifFalseType->isExplicit()) {
					$finalScope = $ifTrueScope;
				} else {
					$finalScope = $ifTrueScope->mergeWith($ifFalseScope);
				}
			}
		}

		// the single-pass payoff: each branch was evaluated on the matching
		// cond-narrowed scope, so the result type composes from the branch
		// results — the old resolveType re-processed the condition on a
		// throwaway storage to rebuild those scopes
		$typeCallback = static function (Expr $e, MutatingScope $s) use ($ternaryCondResult, $ifResult, $elseResult): Type {
			if (!$e instanceof Ternary) {
				throw new ShouldNotHappenException();
			}

			$booleanCondType = $ternaryCondResult->getTypeForScope($s)->toBoolean();

			if ($e->if === null) {
				// short ternary: the truthy value is the condition itself,
				// narrowed by its own truthiness
				$truthyScope = $ternaryCondResult->getTruthyScope();
				if ($s->nativeTypesPromoted) {
					$promotedTruthyScope = $truthyScope->doNotTreatPhpDocTypesAsCertain();
					if (!$promotedTruthyScope instanceof MutatingScope) {
						throw new ShouldNotHappenException();
					}
					$truthyScope = $promotedTruthyScope;
				}

				if ($booleanCondType->isTrue()->yes()) {
					return $ternaryCondResult->getTypeOnScope($truthyScope);
				}

				if ($booleanCondType->isFalse()->yes()) {
					return $elseResult->getTypeForScope($s);
				}

				return TypeCombinator::union(
					TypeCombinator::removeFalsey($ternaryCondResult->getTypeOnScope($truthyScope)),
					$elseResult->getTypeForScope($s),
				);
			}

			if ($ifResult === null) {
				throw new ShouldNotHappenException();
			}

			if ($booleanCondType->isTrue()->yes()) {
				return $ifResult->getTypeForScope($s);
			}

			if ($booleanCondType->isFalse()->yes()) {
				return $elseResult->getTypeForScope($s);
			}

			return TypeCombinator::union(
				$ifResult->getTypeForScope($s),
				$elseResult->getTypeForScope($s),
			);
		};

		return new ExpressionResult(
			$finalScope,
			hasYield: $ternaryCondResult->hasYield(),
			isAlwaysTerminating: $ternaryCondResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			// branch scopes via the specify path (§3.13) — a ternary's narrowing
			// cannot be composed incrementally from one child
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $this->createSpecifyTypesCallback($nodeScopeResolver, $stmt),
		);
	}

	/**
	 * New-world copy of specifyTypes(): the ternary rewrites itself into the
	 * same synthetic disjunction the old world used —
	 * `(cond && if) || (!cond && else)` — and the synthetic is processed on
	 * demand through the migrated BooleanOr/BooleanAnd handlers
	 * (ResultAwareScope tier 4). No seeds: the synthetic's children must be
	 * evaluated on the ask scope (§3.13).
	 *
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(NodeScopeResolver $nodeScopeResolver, Stmt $stmt): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt): SpecifiedTypes {
			if (!$e instanceof Ternary) {
				throw new ShouldNotHappenException();
			}

			if ($e->cond instanceof Ternary || $ctx->null()) {
				return $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx);
			}

			if ($e->if !== null) {
				$conditionExpr = new BooleanOr(
					new BooleanAnd($e->cond, $e->if),
					new BooleanAnd(new Expr\BooleanNot($e->cond), $e->else),
				);
			} else {
				$conditionExpr = new BooleanOr(
					$e->cond,
					new BooleanAnd(new Expr\BooleanNot($e->cond), $e->else),
				);
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			return $this->typeSpecifier->specifyTypesInCondition($adapterScope, $conditionExpr, $ctx)->setRootExpr($e);
		};
	}

}
