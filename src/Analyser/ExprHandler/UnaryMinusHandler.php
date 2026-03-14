<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<UnaryMinus>
 */
#[AutowiredService]
final class UnaryMinusHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof UnaryMinus;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());

		return $this->expressionResultFactory->create(
			$expr,
			$exprResult->getScope(),
			typeCallback: fn (Expr $uninteresting, MutatingScope $scope) => $this->initializerExprTypeResolver->getUnaryMinusType($expr->expr, static function (Expr $e) use ($expr, $exprResult, $nodeScopeResolver, $stmt, $scope): Type {
				if ($e === $expr->expr) {
					return $exprResult->getTypeForScope($scope);
				}

				return $nodeScopeResolver->processExprNode($stmt, $e, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep())->getTypeForScope($scope);
			}),
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->initializerExprTypeResolver->getUnaryMinusType($expr->expr, static fn (Expr $expr): Type => $scope->getType($expr));
	}

}
