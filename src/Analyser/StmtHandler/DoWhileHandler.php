<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\RecordingNodeCallback;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\DoWhileLoopConditionNode;
use function array_merge;
use function count;

/**
 * @implements StmtHandler<Do_>
 */
#[AutowiredService]
final class DoWhileHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Do_;
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
		$finalScope = null;
		$bodyScope = $scope;
		$count = 0;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$originalStorage = $storage;

		$replayBodyRecording = null;
		$replayPassStorage = null;
		$replayPassResult = null;
		$prevEntryScope = null;
		if ($context->isTopLevel()) {
			$bodyIsReplayable = $nodeScopeResolver->isReplayableConvergenceBody($stmt, $stmt->stmts);
			do {
				$prevScope = $bodyScope;
				$bodyScope = $bodyScope->mergeWith($scope);
				if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
					// walking is deterministic in the entry scope - an unchanged entry
					// reproduces the previous pass's exit (and repeats only idempotent
					// merges into the final scope), so the verification walk is skipped
					$bodyScope = $prevScope;
					break;
				}
				$prevEntryScope = $bodyScope;
				$storage = $originalStorage->duplicate();
				$bodyRecording = $bodyIsReplayable ? new RecordingNodeCallback() : new NoopNodeCallback();
				$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $bodyRecording, $context->enterDeep())->filterOutLoopExitPoints();
				$alwaysTerminating = $bodyScopeResult->isAlwaysTerminating();
				$bodyScope = $bodyScopeResult->getScope();
				foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
				}
				$finalScope = $alwaysTerminating ? $finalScope : $bodyScope->mergeWith($finalScope);
				foreach ($bodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
					$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
				}
				// the candidate to replace the final body walk when this pass's
				// entry turns out to be the fixpoint
				if ($bodyRecording instanceof RecordingNodeCallback) {
					$replayBodyRecording = $bodyRecording;
					$replayPassStorage = $storage;
					$replayPassResult = $bodyScopeResult;
				}
				$bodyScope = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep(), null)->getTruthyScope();
				if ($bodyScope->equals($prevScope)) {
					break;
				}

				if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
					$bodyScope = $prevScope->generalizeWith($bodyScope);
				}
				$count++;
			} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);

			$bodyScope = $bodyScope->mergeWith($scope);
		}

		$storage = $originalStorage;
		if (
			$replayBodyRecording !== null && $replayPassStorage !== null && $replayPassResult !== null
			&& $prevEntryScope !== null && $bodyScope->equals($prevEntryScope)
		) {
			// the final body walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's results
			// and replay its emissions through the real callback instead; the
			// condition walks below stay real
			$originalStorage->mergeResults($replayPassStorage);
			$nodeScopeResolver->replayRecording($replayBodyRecording, $nodeCallback, $originalStorage);
			$bodyScopeResult = $replayPassResult;
		} else {
			$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
		}
		$bodyScope = $bodyScopeResult->getScope();
		foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
			$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
		}

		$alwaysIterates = false;
		if ($context->isTopLevel()) {
			$condBooleanType = ($nodeScopeResolver->shouldTreatPhpDocTypesAsCertain() ? $bodyScope->getType($stmt->cond) : $bodyScope->getNativeType($stmt->cond))->toBoolean();
			$alwaysIterates = $condBooleanType->isTrue()->yes();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, new DoWhileLoopConditionNode($stmt->cond, $bodyScopeResult->toPublic()->getExitPoints(), $bodyScopeResult->hasYield()), $bodyScope, $storage);

		if ($alwaysIterates) {
			$alwaysTerminating = count($bodyScopeResult->getExitPointsByType(Break_::class)) === 0;
		} else {
			$alwaysTerminating = $bodyScopeResult->isAlwaysTerminating();
		}
		$finalScope = $alwaysTerminating ? $finalScope : $bodyScope->mergeWith($finalScope);
		if ($finalScope === null) {
			$finalScope = $scope;
		}
		if (!$alwaysTerminating) {
			$condResult = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep(), null);
			$hasYield = $condResult->hasYield();
			$throwPoints = $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			$finalScope = $condResult->getFalseyScope();
		} else {
			$nodeScopeResolver->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep(), null);
		}

		$breakExitPoints = $bodyScopeResult->getExitPointsByType(Break_::class);
		if (count($breakExitPoints) > 0) {
			$breakScope = $alwaysIterates ? null : $finalScope;
			foreach ($breakExitPoints as $breakExitPoint) {
				$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope());
			}
			$finalScope = $breakScope;
		}

		return new InternalStatementResult(
			$finalScope,
			hasYield: $bodyScopeResult->hasYield() || $hasYield,
			isAlwaysTerminating: $alwaysTerminating,
			exitPoints: $bodyScopeResult->getExitPointsForOuterLoop(),
			throwPoints: array_merge($throwPoints, $bodyScopeResult->getThrowPoints()),
			impurePoints: array_merge($impurePoints, $bodyScopeResult->getImpurePoints()),
		);
	}

}
