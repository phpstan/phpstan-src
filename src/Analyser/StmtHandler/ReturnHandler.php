<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Expr\Variable;
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
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use function is_string;

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
		$stmtScope = $nodeScopeResolver->processStmtVarAnnotation($scope, $storage, $stmt, $stmt->expr, $nodeCallback);

		if ($stmt->expr !== null) {
			$result = $nodeScopeResolver->processExprNode($stmt, $stmt->expr, $stmtScope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			// the @var-changed-type node fires now that the expression is stored
			// on the scope BEFORE the @var tag re-typed the expression, so the rule
			// compares the tag against the expression's walked type
			$varConstraints = $nodeScopeResolver->emitVarTagChangedNode($scope, $storage, $stmt, $stmt->expr, $nodeCallback);
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$scope = $result->getScope()->addTemplateArgumentConstraints($varConstraints)->addTemplateArgumentConstraints($nodeScopeResolver->collectReturnSend($stmtScope, $result));
			$hasYield = $result->hasYield();
			$variableFlow = $result->getVariableFlow();
		} else {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$variableFlow = null;
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $stmtScope, $storage);

		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: true, exitPoints: [
			new InternalStatementExitPoint($stmt, $scope),
		], throwPoints: $throwPoints, impurePoints: $impurePoints, variableFlow: VariableFlow::sequence(
			$variableFlow,
			VariableFlow::exit(VariableFlow::RETURN, name: $stmt->expr instanceof Variable && is_string($stmt->expr->name) ? $stmt->expr->name : null),
		));
	}

}
