<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;

/**
 * @implements StmtHandler<Echo_>
 */
#[AutowiredService]
final class EchoHandler implements StmtHandler
{

	public function __construct(private ImplicitToStringCallHelper $implicitToStringCallHelper)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Echo_;
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
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($stmt->exprs as $echoExpr) {
			$result = $nodeScopeResolver->processExprNode($stmt, $echoExpr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($echoExpr, $scope, $result);
			$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);

		$impurePoints[] = new ImpurePoint($scope, $stmt, 'echo', 'echo', true);
		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: $isAlwaysTerminating, exitPoints: [], throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

}
