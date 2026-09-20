<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use Error;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementExitPoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StatementsHandler;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use function array_filter;
use function count;

/**
 * @implements StmtHandler<Expression>
 */
#[AutowiredService]
final class ExpressionHandler implements StmtHandler
{

	public function __construct(
		private StatementsHandler $statementsHandler,
	)
	{
	}

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
		$preAnnotationScope = $scope;
		$stmtScope = $scope;
		if ($stmt->expr instanceof Expr\Throw_) {
			$stmtScope = $this->statementsHandler->processStmtVarAnnotation($nodeScopeResolver, $scope, $storage, $stmt, $stmt->expr->expr, $nodeCallback);
			$scope = $stmtScope;
		}
		$hasAssign = false;
		$currentScope = $scope;
		$nodeScopeResolver->pushNodeGatherer(static function (Node $node, Scope $scope) use ($currentScope, &$hasAssign): void {
			if (
				!($node instanceof VariableAssignNode) && !($node instanceof PropertyAssignNode)
				|| $scope->getAnonymousFunctionReflection() !== $currentScope->getAnonymousFunctionReflection()
				|| $scope->getFunction() !== $currentScope->getFunction()
			) {
				return;
			}

			$hasAssign = true;
		});
		try {
			$result = $nodeScopeResolver->processExprNode($stmt, $stmt->expr, $scope, $storage, $nodeCallback, ExpressionContext::createTopLevel($context->shouldResolveTemplateArguments()));
			if ($stmt->expr instanceof Expr\Throw_) {
				// the @var-changed-type node fires now that the thrown expression is stored
				$result = $result->withScope($result->getScope()->addTemplateArgumentConstraints($this->statementsHandler->emitVarTagChangedNode($nodeScopeResolver, $preAnnotationScope, $storage, $stmt, $stmt->expr->expr, $nodeCallback)));
			}
		} finally {
			$nodeScopeResolver->popNodeGatherer();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $stmtScope, $storage);
		// Errors signal programmer mistakes (ValueError, TypeError, DivisionByZeroError...),
		// nobody calls an otherwise pure expression just to have them thrown, so they
		// do not make the expression statement meaningful. A `throw` written in the
		// statement is a different story - throwing is the whole point of it.
		$errorType = new ObjectType(Error::class);
		$throwPoints = array_filter($result->getThrowPoints(), static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() && ($throwPoint->isFromThrowExpr() || !$errorType->isSuperTypeOf($throwPoint->getType())->yes()));
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
		$specifiedTypes = $result->getSpecifiedTypesForScope($scope, TypeSpecifierContext::createNull());
		$scope = $scope->applySpecifiedTypes($specifiedTypes);

		if ($specifiedTypes->isEquality()) {
			// Statement counterpart of ExpressionResult's equality handling:
			// store the call's true result so a duplicate void assertion statement is
			// reported as always-true. We assign directly because void calls have no
			// return value to protect, and intersecting true with void would produce never.
			$scope = $scope->assignExpression($stmt->expr, new ConstantBooleanType(true), new ConstantBooleanType(true));
		}

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
			], throwPoints: $throwPoints, impurePoints: $impurePoints, variableFlow: $result->getVariableFlow());
		}
		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: $isAlwaysTerminating, exitPoints: [], throwPoints: $throwPoints, impurePoints: $impurePoints, variableFlow: $result->getVariableFlow());
	}

}
