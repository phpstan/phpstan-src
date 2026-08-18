<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Const_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;

/**
 * @implements StmtHandler<Const_>
 */
#[AutowiredService]
final class ConstHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Const_;
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
		$impurePoints = [];
		foreach ($stmt->consts as $const) {
			$constResult = $nodeScopeResolver->processExprNode($stmt, $const->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			// the constant's callback fires after its value was processed, so
			// rule-side asks about the value answer from the storage
			$nodeScopeResolver->callNodeCallback($nodeCallback, $const, $scope, $storage);
			$impurePoints = array_merge($impurePoints, $constResult->getImpurePoints());
			if ($const->namespacedName !== null) {
				$constantName = new Name\FullyQualified($const->namespacedName->toString());
			} else {
				$constantName = new Name\FullyQualified($const->name->toString());
			}
			$scope = $scope->assignExpression(new ConstFetch($constantName), $constResult->getType(), $constResult->getNativeType());
		}

		// deferred from processStmtNode() - fires after the values were processed
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: $impurePoints);
	}

}
