<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementExitPoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements StmtHandler<Return_>
 */
#[AutowiredService]
final class ReturnHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Return_;
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
		if ($stmt->expr !== null) {
			$result = $nodeScopeResolver->processExprNode($stmt, $stmt->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep(), null);
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
		} else {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);

		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: true, exitPoints: [
			new InternalStatementExitPoint($stmt, $scope),
		], throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

}
