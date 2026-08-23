<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements StmtHandler<EnumCase>
 */
#[AutowiredService]
final class EnumCaseHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof EnumCase;
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
		$nodeScopeResolver->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
		$impurePoints = [];
		if ($stmt->expr !== null) {
			$exprResult = $nodeScopeResolver->processExprNode($stmt, $stmt->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$impurePoints = $exprResult->getImpurePoints();
		}

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: $impurePoints);
	}

}
