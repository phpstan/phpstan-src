<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements StmtHandler<Use_>
 */
#[AutowiredService]
final class UseHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Use_;
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
		foreach ($stmt->uses as $use) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $use, $scope, $storage);
		}

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
	}

}
