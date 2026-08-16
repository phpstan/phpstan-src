<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Switch_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\SwitchConditionArm;
use PHPStan\Node\SwitchConditionNode;
use PHPStan\Type\NeverType;
use function array_merge;

/**
 * @implements StmtHandler<Switch_>
 */
#[AutowiredService]
final class SwitchHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Switch_;
	}

	public function __construct(private Container $container)
	{
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
		$entryScope = $scope;
		$condResult = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
		$scope = $condResult->getScope();
		$scopeForBranches = $scope;
		$finalScope = null;
		$prevScope = null;
		$hasDefaultCase = false;
		$alwaysTerminating = true;
		$hasYield = $condResult->hasYield();
		$exitPointsForOuterLoop = [];
		$throwPoints = $condResult->getThrowPoints();
		$impurePoints = $condResult->getImpurePoints();
		$fullCondExpr = null;
		$switchConditionArms = [];
		$lastNonDefaultCaseKey = null;
		foreach ($stmt->cases as $caseKey => $caseNode) {
			if ($caseNode->cond === null) {
				continue;
			}

			$lastNonDefaultCaseKey = $caseKey;
		}
		foreach ($stmt->cases as $caseKey => $caseNode) {
			if ($caseNode->cond !== null) {
				$condExpr = new BinaryOp\Equal($stmt->cond, $caseNode->cond);
				$fullCondExpr = $fullCondExpr === null ? $condExpr : new BooleanOr($fullCondExpr, $condExpr);
				$caseResult = $nodeScopeResolver->processExprNode($stmt, $caseNode->cond, $scopeForBranches, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scopeForBranches = $caseResult->getScope();
				$hasYield = $hasYield || $caseResult->hasYield();
				$throwPoints = array_merge($throwPoints, $caseResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $caseResult->getImpurePoints());
				$switchConditionArms[] = new SwitchConditionArm(
					$caseNode->cond,
					$scopeForBranches,
					$caseNode->cond->getStartLine(),
					$caseKey === $lastNonDefaultCaseKey,
				);
				// the == narrowing composed from the subject's and the case's
				// results (what the walked synthetic delegates to); the walk is
				// the composition's miss seam
				$caseEqualTypes = $this->container->getByType(IdenticalNarrowingHelper::class)->specifyEqual(
					$nodeScopeResolver,
					$stmt->cond,
					$caseNode->cond,
					$condResult,
					$caseResult,
					TypeSpecifierContext::createTruthy(),
					$caseResult->getScope(),
					null,
					null,
				);
				$branchScope = $caseEqualTypes !== null
					? $caseResult->getScope()->applySpecifiedTypes($caseEqualTypes->setRootExpr($condExpr))
					: $nodeScopeResolver->narrowScopeWithCondition($caseResult->getScope(), $condExpr, TypeSpecifierContext::createTruthy());
			} else {
				$hasDefaultCase = true;
				$fullCondExpr = null;
				$branchScope = $scopeForBranches;
			}

			$branchScope = $branchScope->mergeWith($prevScope);
			$branchScopeResult = $nodeScopeResolver->processStmtNodesInternal($caseNode, $caseNode->stmts, $branchScope, $storage, $nodeCallback, $context);
			$branchScope = $branchScopeResult->getScope();
			$branchFinalScopeResult = $branchScopeResult->filterOutLoopExitPoints();
			$hasYield = $hasYield || $branchFinalScopeResult->hasYield();
			foreach ($branchScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
				$alwaysTerminating = false;
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
			}
			foreach ($branchScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
			}
			$exitPointsForOuterLoop = array_merge($exitPointsForOuterLoop, $branchFinalScopeResult->getExitPointsForOuterLoop());
			$throwPoints = array_merge($throwPoints, $branchFinalScopeResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $branchFinalScopeResult->getImpurePoints());
			if ($branchScopeResult->isAlwaysTerminating()) {
				$alwaysTerminating = $alwaysTerminating && $branchFinalScopeResult->isAlwaysTerminating();
				$prevScope = null;
				if (isset($fullCondExpr)) {
					$scopeForBranches = $nodeScopeResolver->narrowScopeWithCondition($scopeForBranches, $fullCondExpr, TypeSpecifierContext::createFalsey());
					$fullCondExpr = null;
				}
				if (!$branchFinalScopeResult->isAlwaysTerminating()) {
					$finalScope = $branchScope->mergeWith($finalScope);
				}
			} else {
				$prevScope = $branchScope;
			}
		}

		// the Switch_ callback is deferred from processStmtNode(): it fires
		// after every case condition's walk stored its result, with the entry
		// scope, so rules pricing the case conditions answer from the storage
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);

		if ($switchConditionArms !== []) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, new SwitchConditionNode($stmt->cond, $switchConditionArms, $stmt), $scope, $storage);
		}

		// $scopeForBranches is the subject narrowed by "none of the cases
		// matched". The narrowing is tracked by the scope (getTypeOnScope's
		// authoritative read); only an untracked subject needs reprocessing there.
		$remainingCaseType = $condResult->answersOnScope($scopeForBranches, false)
			? $condResult->getTypeOnScope($scopeForBranches, false)
			: $nodeScopeResolver->processExprOnDemand($stmt->cond, $scopeForBranches, new ExpressionResultStorage())->getType();
		$exhaustive = $remainingCaseType instanceof NeverType;

		if (!$hasDefaultCase && !$exhaustive) {
			$alwaysTerminating = false;
		}

		if ($prevScope !== null && isset($branchFinalScopeResult)) {
			$finalScope = $prevScope->mergeWith($finalScope);
			$alwaysTerminating = $alwaysTerminating && $branchFinalScopeResult->isAlwaysTerminating();
		}

		if ((!$hasDefaultCase && !$exhaustive) || $finalScope === null) {
			$finalScope = $scopeForBranches->mergeWith($finalScope);
		}

		return new InternalStatementResult($finalScope, hasYield: $hasYield, isAlwaysTerminating: $alwaysTerminating, exitPoints: $exitPointsForOuterLoop, throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

}
