<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\If_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalEndStatementResult;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;
use function array_reverse;
use function count;

/**
 * @implements StmtHandler<If_>
 */
#[AutowiredService]
final class IfHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof If_;
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
		$flowBranches = [];
		$elseFlow = null;
		$condResult = $nodeScopeResolver->processExprNode($stmt, $stmt->cond, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);
		$conditionType = ($nodeScopeResolver->shouldTreatPhpDocTypesAsCertain() ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
		$ifAlwaysTrue = $conditionType->isTrue()->yes();
		$exitPoints = [];
		$throwPoints = $condResult->getThrowPoints();
		$impurePoints = $condResult->getImpurePoints();
		$endStatements = [];
		$finalScope = null;
		$alwaysTerminating = true;
		$hasYield = $condResult->hasYield();
		$branchScopeStatementResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $condResult->getTruthyScope(), $storage, $nodeCallback, $context);
		$flowBranches[] = [$condResult->getVariableFlow(), $branchScopeStatementResult->getVariableFlow(), $conditionType->isTrue()->yes() ? true : ($conditionType->isFalse()->yes() ? false : null)];
		if (!$conditionType->isTrue()->no()) {
			$exitPoints = $branchScopeStatementResult->getExitPoints();
			$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $branchScopeStatementResult->getImpurePoints());
			$branchScope = $branchScopeStatementResult->getScope();
			$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? null : $branchScope;
			$alwaysTerminating = $branchScopeStatementResult->isAlwaysTerminating();
			if (count($branchScopeStatementResult->getEndStatements()) > 0) {
				$endStatements = array_merge($endStatements, $branchScopeStatementResult->getEndStatements());
			} elseif (count($stmt->stmts) > 0) {
				$endStatements[] = new InternalEndStatementResult($stmt->stmts[count($stmt->stmts) - 1], $branchScopeStatementResult);
			} else {
				$endStatements[] = new InternalEndStatementResult($stmt, $branchScopeStatementResult);
			}
			$hasYield = $branchScopeStatementResult->hasYield() || $hasYield;
		}

		$scope = $condResult->getFalseyScope();
		$lastElseIfConditionIsTrue = false;

		$condScope = $scope;
		foreach ($stmt->elseifs as $elseif) {
			$condResult = $nodeScopeResolver->processExprNode($stmt, $elseif->cond, $condScope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$nodeScopeResolver->callNodeCallback($nodeCallback, $elseif, $scope, $storage);
			$elseIfConditionType = ($nodeScopeResolver->shouldTreatPhpDocTypesAsCertain() ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
			$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $condResult->getImpurePoints());
			$branchScopeStatementResult = $nodeScopeResolver->processStmtNodesInternal($elseif, $elseif->stmts, $condResult->getTruthyScope(), $storage, $nodeCallback, $context);
			$flowBranches[] = [$condResult->getVariableFlow(), $branchScopeStatementResult->getVariableFlow(), $elseIfConditionType->isTrue()->yes() ? true : ($elseIfConditionType->isFalse()->yes() ? false : null)];
			if (
				!$ifAlwaysTrue
				&& !$lastElseIfConditionIsTrue
				&& !$elseIfConditionType->isTrue()->no()
			) {
				$exitPoints = array_merge($exitPoints, $branchScopeStatementResult->getExitPoints());
				$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $branchScopeStatementResult->getImpurePoints());
				$branchScope = $branchScopeStatementResult->getScope();
				$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope, true);
				$alwaysTerminating = $alwaysTerminating && $branchScopeStatementResult->isAlwaysTerminating();
				if (count($branchScopeStatementResult->getEndStatements()) > 0) {
					$endStatements = array_merge($endStatements, $branchScopeStatementResult->getEndStatements());
				} elseif (count($elseif->stmts) > 0) {
					$endStatements[] = new InternalEndStatementResult($elseif->stmts[count($elseif->stmts) - 1], $branchScopeStatementResult);
				} else {
					$endStatements[] = new InternalEndStatementResult($elseif, $branchScopeStatementResult);
				}
				$hasYield = $hasYield || $branchScopeStatementResult->hasYield();
			}

			if (
				$elseIfConditionType->isTrue()->yes()
			) {
				$lastElseIfConditionIsTrue = true;
			}

			$condScope = $condResult->getFalseyScope();
			$scope = $condScope;
		}

		if ($stmt->else === null) {
			if (!$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
				$finalScope = $scope->mergeWith($finalScope, true);
				$alwaysTerminating = false;
			}
		} else {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt->else, $scope, $storage);
			$branchScopeStatementResult = $nodeScopeResolver->processStmtNodesInternal($stmt->else, $stmt->else->stmts, $scope, $storage, $nodeCallback, $context);
			$elseFlow = $branchScopeStatementResult->getVariableFlow();
			if (!$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
				$exitPoints = array_merge($exitPoints, $branchScopeStatementResult->getExitPoints());
				$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $branchScopeStatementResult->getImpurePoints());
				$branchScope = $branchScopeStatementResult->getScope();
				$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope, true);
				$alwaysTerminating = $alwaysTerminating && $branchScopeStatementResult->isAlwaysTerminating();
				if (count($branchScopeStatementResult->getEndStatements()) > 0) {
					$endStatements = array_merge($endStatements, $branchScopeStatementResult->getEndStatements());
				} elseif (count($stmt->else->stmts) > 0) {
					$endStatements[] = new InternalEndStatementResult($stmt->else->stmts[count($stmt->else->stmts) - 1], $branchScopeStatementResult);
				} else {
					$endStatements[] = new InternalEndStatementResult($stmt->else, $branchScopeStatementResult);
				}
				$hasYield = $hasYield || $branchScopeStatementResult->hasYield();
			}
		}

		if ($finalScope === null) {
			$finalScope = $scope;
		}

		if ($stmt->else === null && !$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
			$endStatements[] = new InternalEndStatementResult($stmt, new InternalStatementResult($finalScope, hasYield: $hasYield, isAlwaysTerminating: $alwaysTerminating, exitPoints: $exitPoints, throwPoints: $throwPoints, impurePoints: $impurePoints));
		}

		foreach (array_reverse($flowBranches) as [$conditionFlow, $branchFlow, $truthy]) {
			$elseFlow = VariableFlow::conditional($conditionFlow, $branchFlow, $elseFlow, $truthy);
		}
		return new InternalStatementResult($finalScope, hasYield: $hasYield, isAlwaysTerminating: $alwaysTerminating, exitPoints: $exitPoints, throwPoints: $throwPoints, impurePoints: $impurePoints, endStatements: $endStatements, variableFlow: $elseFlow);
	}

}
