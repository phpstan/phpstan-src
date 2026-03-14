<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<Closure>
 */
#[AutowiredService]
final class ClosureHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private ClosureTypeResolver $closureTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Closure;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$processClosureResult = $nodeScopeResolver->processClosureNode($stmt, $expr, $scope, $storage, $nodeCallback, $context, null);
		$scope = $processClosureResult->applyByRefUseScope($processClosureResult->getScope());

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->closureTypeResolver->getClosureType($scope, $expr);
	}

}
