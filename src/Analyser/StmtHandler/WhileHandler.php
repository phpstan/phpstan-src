<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\While_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BreaklessWhileLoopNode;
use function array_merge;
use function count;

/**
 * @implements StmtHandler<While_>
 */
#[AutowiredService]
final class WhileHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof While_;
	}

	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$originalStorage = $storage;
		$storage = $originalStorage->duplicate();
		$condResult = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $scope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep());
		$beforeCondBooleanType = ($nodeScopeResolver->shouldTreatPhpDocTypesAsCertain() ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
		$condScope = $condResult->getFalseyScope();
		if (!$context->isTopLevel() && $beforeCondBooleanType->isFalse()->yes()) {
			if (!$nodeScopeResolver->shouldPolluteScopeWithLoopInitialAssignments()) {
				$scope = $condScope->mergeWith($scope);
			}

			return new InternalStatementResult(
				$scope,
				hasYield: $condResult->hasYield(),
				isAlwaysTerminating: false,
				exitPoints: [],
				throwPoints: $condResult->getThrowPoints(),
				impurePoints: $condResult->getImpurePoints(),
			);
		}
		$bodyScope = $condResult->getTruthyScope();

		if ($context->isTopLevel()) {
			$count = 0;
			$prevEntryScope = null;
			do {
				$prevScope = $bodyScope;
				$bodyScope = $bodyScope->mergeWith($scope);
				if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
					// walking is deterministic in the entry scope - an unchanged entry
					// reproduces the previous pass's exit, so the verification walk is skipped
					$bodyScope = $prevScope;
					break;
				}
				$prevEntryScope = $bodyScope;
				$storage = $originalStorage->duplicate();
				$bodyScope = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep())->getTruthyScope();
				$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
				$bodyScope = $bodyScopeResult->getScope();
				foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
				}
				if ($bodyScope->equals($prevScope)) {
					break;
				}

				if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
					$bodyScope = $prevScope->generalizeWith($bodyScope);
				}
				$count++;
			} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);
		}

		$bodyScope = $bodyScope->mergeWith($scope);
		$bodyScopeMaybeRan = $bodyScope;
		$storage = $originalStorage;
		$bodyScope = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep())->getTruthyScope();
		$finalScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
		$finalScope = $finalScopeResult->getScope()->filterByFalseyValue($stmt->cond);

		$alwaysIterates = false;
		$neverIterates = false;
		if ($context->isTopLevel()) {
			$condBooleanType = ($nodeScopeResolver->shouldTreatPhpDocTypesAsCertain() ? $bodyScopeMaybeRan->getType($stmt->cond) : $bodyScopeMaybeRan->getNativeType($stmt->cond))->toBoolean();
			$alwaysIterates = $condBooleanType->isTrue()->yes();
			$neverIterates = $condBooleanType->isFalse()->yes();
		}
		if (!$alwaysIterates) {
			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$finalScope = $finalScope->mergeWith($continueExitPoint->getScope());
			}
		}

		$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
		if (count($breakExitPoints) > 0) {
			$breakScope = $alwaysIterates ? null : $finalScope;
			foreach ($breakExitPoints as $breakExitPoint) {
				$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope());
			}
			$finalScope = $breakScope;
		}

		$isIterableAtLeastOnce = $beforeCondBooleanType->isTrue()->yes();
		$nodeScopeResolver->callNodeCallback($nodeCallback, new BreaklessWhileLoopNode($stmt, $finalScopeResult->toPublic()->getExitPoints(), $finalScopeResult->hasYield()), $bodyScopeMaybeRan, $storage);

		if ($alwaysIterates) {
			$isAlwaysTerminating = count($finalScopeResult->getExitPointsByType(Break_::class)) === 0;
		} elseif ($isIterableAtLeastOnce) {
			$isAlwaysTerminating = $finalScopeResult->isAlwaysTerminating();
		} else {
			$isAlwaysTerminating = false;
		}
		if (!$isIterableAtLeastOnce) {
			if (!$nodeScopeResolver->shouldPolluteScopeWithLoopInitialAssignments()) {
				$condScope = $condScope->mergeWith($scope);
			}
			$finalScope = $finalScope->mergeWith($condScope);
		}

		$throwPoints = $condResult->getThrowPoints();
		$impurePoints = $condResult->getImpurePoints();
		if (!$neverIterates) {
			$throwPoints = array_merge($throwPoints, $finalScopeResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $finalScopeResult->getImpurePoints());
		}

		return new InternalStatementResult(
			$finalScope,
			hasYield: $finalScopeResult->hasYield() || $condResult->hasYield(),
			isAlwaysTerminating: $isAlwaysTerminating,
			exitPoints: $finalScopeResult->getExitPointsForOuterLoop(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

}
