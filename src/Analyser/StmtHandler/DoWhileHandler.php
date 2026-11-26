<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Do_;
use PHPStan\Analyser\ConvergedLoopHeads;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\LoopWrittenVariableNames;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\RecordingNodeCallback;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\DoWhileLoopConditionNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function array_merge;
use function count;

/**
 * @implements StmtHandler<Do_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/DoWhileHandler.cpp')]
final class DoWhileHandler implements StmtHandler
{

	public function __construct(
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

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
		$writtenVariableNames = null;
		$count = 0;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$originalStorage = $storage;

		$replayBodyRecording = null;
		$replayPassStorage = null;
		$replayPassResult = null;
		$prevEntryScope = null;
		$previousPass = null;
		$previousPassStorage = null;
		$loopHeads = null;
		$entryConverged = false;
		// the loop's entry joined with the head it converged to when walked before
		$loopEntryScope = $scope;
		if ($context->shouldConvergeLoops()) {
			$loopHeads = $context->getLoopHeads() ?? new ConvergedLoopHeads();
			// heads come from pass walks - a top-level walk converges from its own entry
			if (!$context->isTopLevel()) {
				$loopEntryScope = $loopHeads->warmStart($stmt, $scope);
				if ($loopEntryScope !== $scope) {
					$bodyScope = $loopEntryScope;
				}
			}
			$bodyIsReplayable = !$context->isTopLevel() || $nodeScopeResolver->isReplayableConvergenceBody($stmt, $stmt->stmts);
			// each pass after the first re-walks only the statements whose
			// entry state differs from the previous pass's (StatementsHandler::processLoopPassStmtNodes())
			do {
				$prevScope = $bodyScope;
				// a warm start begins at the loop's entry already
				$bodyScope = $bodyScope === $loopEntryScope ? $bodyScope : $bodyScope->mergeWith($loopEntryScope, true);
				if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
					// walking is deterministic in the entry scope - an unchanged entry
					// reproduces the previous pass's exit (and repeats only idempotent
					// merges into the final scope), so the verification walk is skipped
					$bodyScope = $prevEntryScope;
					$entryConverged = true;
					break;
				}
				$prevEntryScope = $bodyScope;
				$storage = $originalStorage->duplicate();
				if ($previousPassStorage !== null) {
					// the statements this pass replays answer from the results the previous pass stored
					$storage->mergeResults($previousPassStorage);
				}
				$bodyRecording = new RecordingNodeCallback();
				$scope->pushExpressionResultStorage($storage);
				try {
					$passRecord = null;
					$bodyScopeResult = $nodeScopeResolver->processLoopPassStmtNodes($stmt, $stmt->stmts, $bodyScope, $storage, $bodyRecording, $context->enterLoopPass($loopHeads), $previousPass, $passRecord)->filterOutLoopExitPoints();
					$previousPass = $passRecord;
					$previousPassStorage = $storage;
					$backEdgeScope = $bodyScopeResult->getLoopBackEdgeScope();
					$finalScope = $backEdgeScope === null ? $finalScope : $backEdgeScope->mergeWith($finalScope, true);
					foreach ($bodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
						$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope, true);
					}
					// the candidate to replace the final body walk when this pass's
					// entry turns out to be the fixpoint
					if ($bodyIsReplayable) {
						$replayBodyRecording = $bodyRecording;
						$replayPassStorage = $storage;
						$replayPassResult = $bodyScopeResult;
					}
					if ($backEdgeScope === null) {
						$bodyScope = $prevScope;
						break;
					}
					$passCondResult = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $backEdgeScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep(resolveTemplateArguments: false));
					$bodyScope = $passCondResult->getTruthyScope();
				} finally {
					$scope->popExpressionResultStorage();
				}
				if ($bodyScope->equals($prevScope)) {
					break;
				}

				if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
					$writtenVariableNames = LoopWrittenVariableNames::collect($stmt, VariableFlow::sequence($bodyScopeResult->getVariableFlow(), $passCondResult->getVariableFlow()));
					$bodyScope = $prevScope->generalizeWith($bodyScope, $writtenVariableNames, $loopEntryScope);
				}
				$count++;
			} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);
			$iterationLimitReached = $count >= NodeScopeResolver::LOOP_SCOPE_ITERATIONS;
			if ($iterationLimitReached) {
				// the limit is only a safety net - a scope still changing after it
				// is not a fixpoint, so nothing the loop writes keeps a type
				$bodyScope = $bodyScope->forgetLoopWrittenVariables($writtenVariableNames);
			}

			if (!$entryConverged) {
				// a body state that did not converge may still reach the guards
				// a vacuous conditional excludes, so only a fixpoint keeps them
				$bodyScope = $bodyScope->mergeWith($loopEntryScope, !$iterationLimitReached);
			}
			if (!$context->isTopLevel()) {
				$loopHeads->record($stmt, $bodyScope);
			}
		}

		$storage = $originalStorage;
		if (
			$replayBodyRecording !== null
			&& $bodyScope->equals($prevEntryScope)
		) {
			// the final body walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's results
			// and replay its emissions through the real callback instead; the
			// condition walks below stay real
			$originalStorage->mergeResults($replayPassStorage);
			$nodeScopeResolver->replayRecording($replayBodyRecording, $nodeCallback, $originalStorage, $scope);
			$bodyScopeResult = $replayPassResult;
		} elseif ($previousPass !== null && $previousPass[1] !== null) {
			// the last pass stands in for the statements whose state it walked
			// with - they replay through the real callback
			$originalStorage->mergeResults($previousPassStorage);
			$bodyScopeResult = $nodeScopeResolver->processLoopFinalStmtNodes($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context->withLoopHeads($loopHeads), $previousPass)->filterOutLoopExitPoints();
		} else {
			$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context->withLoopHeads($loopHeads))->filterOutLoopExitPoints();
		}
		$backEdgeScope = $bodyScopeResult->getLoopBackEdgeScope();
		$backEdgeDead = $backEdgeScope === null;
		$bodyScope = $backEdgeScope ?? $bodyScopeResult->getScope();

		// the condition is processed once on the post-body scope; its result
		// answers both the always-iterates check below and the falsey post-loop
		// scope - the previous scope-based read here was a guaranteed storage
		// miss (the condition was only ever stored into discarded convergence
		// duplicates) that re-priced the condition on demand before this walk
		$condResult = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));

		$alwaysIterates = false;
		if ($context->isTopLevel()) {
			$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
			$alwaysIterates = $condBooleanType->isTrue()->yes();
		}

		if ($alwaysIterates || $backEdgeDead) {
			$alwaysTerminating = count($bodyScopeResult->getExitPointsByType(Break_::class)) === 0;
		} else {
			$alwaysTerminating = $bodyScopeResult->isAlwaysTerminating();
		}
		$finalScope = $alwaysTerminating || $backEdgeDead ? $finalScope : $bodyScope->mergeWith($finalScope, true);
		if ($finalScope === null) {
			$finalScope = $scope;
		}
		if (!$alwaysTerminating && !$backEdgeDead) {
			$hasYield = $condResult->hasYield();
			$throwPoints = $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			$finalScope = $condResult->getFalseyScope();
		}

		// both emissions fire after the condition's final walk stored its
		// results, so rule-side asks about the condition answer from the
		// storage; the Do_ callback is deferred from processStmtNode()
		$nodeScopeResolver->callNodeCallback($nodeCallback, new DoWhileLoopConditionNode($stmt->cond, $bodyScopeResult->toPublic()->getExitPoints(), $bodyScopeResult->hasYield()), $bodyScope, $storage);
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $scope, $storage);

		$breakExitPoints = $bodyScopeResult->getExitPointsByType(Break_::class);
		if (count($breakExitPoints) > 0) {
			$breakScope = $alwaysIterates || $backEdgeDead ? null : $finalScope;
			foreach ($breakExitPoints as $breakExitPoint) {
				$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope(), true);
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
			// the body runs once, then may repeat or not whatever the condition says
			variableFlow: VariableFlow::loop(null, $bodyScopeResult->getVariableFlow(), $condResult->getVariableFlow(), true, true),
			dependencies: Dependencies::merge($bodyScopeResult->getDependencies(), $condResult->getDependencies()),
		);
	}

}
