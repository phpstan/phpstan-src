<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
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
 * @implements StmtHandler<Break_|Continue_>
 */
#[AutowiredService]
final class BreakContinueHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Break_ || $stmt instanceof Continue_;
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
		if ($stmt->num !== null) {
			$result = $nodeScopeResolver->processExprNode($stmt, $stmt->num, $scope, $storage, $nodeCallback, ExpressionContext::createDeep(), null);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
		} else {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
		}

		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: true, exitPoints: [
			new InternalStatementExitPoint($stmt, $scope),
		], throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

}
