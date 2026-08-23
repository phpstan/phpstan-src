<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements StmtHandler<Namespace_>
 */
#[AutowiredService]
final class NamespaceHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Namespace_;
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
		if ($stmt->name !== null) {
			$scope = $scope->enterNamespace($stmt->name->toString());
		} else {
			$scope = $scope->enterNamespace('');
		}

		$scope = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context)->getScope();

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

}
