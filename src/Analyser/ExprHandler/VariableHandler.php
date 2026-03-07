<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use function in_array;

/**
 * @implements ExprHandler<Variable>
 */
#[AutowiredService]
final class VariableHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Variable;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		if ($expr->name instanceof Expr) {
			$result = $this->nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
		} elseif (in_array($expr->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
			$impurePoints[] = new ImpurePoint($scope, $expr, 'superglobal', 'access to superglobal variable', true);
		}
		return new ExpressionResult(
			$scope,
			$hasYield,
			$isAlwaysTerminating,
			$throwPoints,
			$impurePoints,
			static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
