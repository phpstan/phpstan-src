<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Exit_>
 */
#[AutowiredService]
final class ExitHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Exit_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$kind = $expr->getAttribute('kind', Exit_::KIND_EXIT);
		$identifier = $kind === Exit_::KIND_DIE ? 'die' : 'exit';
		$impurePoints = [
			new ImpurePoint($scope, $expr, $identifier, $identifier, true),
		];

		$hasYield = false;
		$throwPoints = [];
		if ($expr->expr !== null) {
			$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$scope = $result->getScope();
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: true,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return new NonAcceptingNeverType();
	}

}
