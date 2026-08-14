<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Cast\String_>
 */
#[AutowiredService]
final class CastStringHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Cast\String_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$impurePoints = $exprResult->getImpurePoints();
		$throwPoints = $exprResult->getThrowPoints();

		$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $scope, $exprResult);
		$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());

		$scope = $exprResult->getScope();

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->initializerExprTypeResolver->getCastType($expr, static function (Expr $e) use ($nativeTypesPromoted, $expr, $exprResult): Type {
				if ($e === $expr->expr) {
					return $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType();
				}

					throw new ShouldNotHappenException();
			}),
			specifyTypesCallback: static fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => ($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope)->obtainResultForNode(
				new NotEqual($expr->expr, new String_('')),
			)->getSpecifiedTypes($context, $nativeTypesPromoted)->setRootExpr($expr),
		);
	}

}
