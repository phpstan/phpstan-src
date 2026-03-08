<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;
use function in_array;

/**
 * @implements ExprHandler<Include_>
 */
#[AutowiredService]
final class IncludeHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Include_;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return new MixedType();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$identifier = in_array($expr->type, [Include_::TYPE_INCLUDE, Include_::TYPE_INCLUDE_ONCE], true) ? 'include' : 'require';
		$scope = $result->getScope()->afterExtractCall();

		return new ExpressionResult(
			$scope,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: array_merge($result->getThrowPoints(), [InternalThrowPoint::createImplicit($scope, $expr)]),
			impurePoints: array_merge($result->getImpurePoints(), [new ImpurePoint($scope, $expr, $identifier, $identifier, true)]),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
