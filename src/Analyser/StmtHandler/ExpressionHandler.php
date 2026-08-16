<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\GatheringNodeCallback;
use PHPStan\Analyser\InternalStatementExitPoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Type\NeverType;
use function array_filter;
use function count;

/**
 * @implements StmtHandler<Expression>
 */
#[AutowiredService]
final class ExpressionHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Expression;
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
		$stmtScope = $scope;
		if ($stmt->expr instanceof Expr\Throw_) {
			$stmtScope = $nodeScopeResolver->processStmtVarAnnotation($scope, $storage, $stmt, $stmt->expr->expr, $nodeCallback);
			$scope = $stmtScope;
		}
		$hasAssign = false;
		$currentScope = $scope;
		$result = $nodeScopeResolver->processExprNode($stmt, $stmt->expr, $scope, $storage, new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($currentScope, &$hasAssign): void {
			if (
				!($node instanceof VariableAssignNode) && !($node instanceof PropertyAssignNode)
				|| $scope->getAnonymousFunctionReflection() !== $currentScope->getAnonymousFunctionReflection()
				|| $scope->getFunction() !== $currentScope->getFunction()
			) {
				return;
			}

			$hasAssign = true;
		}, $nodeCallback), ExpressionContext::createTopLevel());
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $stmtScope, $storage);
		$throwPoints = array_filter($result->getThrowPoints(), static fn ($throwPoint) => $throwPoint->isExplicit());
		if (
			count($result->getImpurePoints()) === 0
			&& count($throwPoints) === 0
			&& !$stmt->expr instanceof Expr\PostInc
			&& !$stmt->expr instanceof Expr\PreInc
			&& !$stmt->expr instanceof Expr\PostDec
			&& !$stmt->expr instanceof Expr\PreDec
		) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, new NoopExpressionNode($stmt->expr, $hasAssign), $scope, $storage);
		}
		$scope = $result->getScope();
		// the expression statement was just processed; read its narrowing from
		// the result instead of re-resolving it via specifyTypesInCondition().
		$scope = $scope->applySpecifiedTypes($result->getSpecifiedTypesForScope($scope, TypeSpecifierContext::createNull()));
		$hasYield = $result->hasYield();
		$throwPoints = $result->getThrowPoints();
		$impurePoints = $result->getImpurePoints();
		$isAlwaysTerminating = $result->isAlwaysTerminating();

		// The expression statement is an exit point when its value type is an
		// explicit never: exit/die/throw, a never-returning call, or a call
		// configured as early-terminating (the call handlers give those never).
		$statementType = $result->getType();
		if ($statementType instanceof NeverType && $statementType->isExplicit()) {
			return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: true, exitPoints: [
				new InternalStatementExitPoint($stmt, $scope),
			], throwPoints: $throwPoints, impurePoints: $impurePoints);
		}
		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: $isAlwaysTerminating, exitPoints: [], throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

}
