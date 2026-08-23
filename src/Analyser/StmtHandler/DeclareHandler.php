<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Declare_;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements StmtHandler<Declare_>
 */
#[AutowiredService]
final class DeclareHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Declare_;
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
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$alwaysTerminating = false;
		$exitPoints = [];
		foreach ($stmt->declares as $declare) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $declare, $scope, $storage);
			$nodeScopeResolver->callNodeCallback($nodeCallback, $declare->value, $scope, $storage);
			if (
				$declare->key->name !== 'strict_types'
				|| !($declare->value instanceof Int_)
				|| $declare->value->value !== 1
			) {
				continue;
			}

			$scope = $scope->enterDeclareStrictTypes();
		}

		if ($stmt->stmts !== null) {
			$result = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$alwaysTerminating = $result->isAlwaysTerminating();
			$exitPoints = $result->getExitPoints();
		}

		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: $alwaysTerminating, exitPoints: $exitPoints, throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

}
