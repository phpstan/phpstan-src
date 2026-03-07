<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use function array_merge;

/**
 * @implements ExprHandler<NullsafePropertyFetch>
 */
#[AutowiredService]
final class NullsafePropertyFetchHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private NonNullabilityHelper $nonNullabilityHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof NullsafePropertyFetch;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureShallowNonNullability($scope, $scope, $expr->var);
		$attributes = array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch' => true]);
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$exprResult = $this->nodeScopeResolver->processExprNode($stmt, new PropertyFetch(
			$expr->var,
			$expr->name,
			$attributes,
		), $nonNullabilityResult->getScope(), $storage, $nodeCallback, $context);
		$scope = $this->nonNullabilityHelper->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
