<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\Generator\NoopNodeCallback;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<Expr\Cast\Bool_>
 */
#[AutowiredService]
final class CastBoolHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Expr\Cast\Bool_;
	}

	public function analyseExpr(
		Stmt $stmt,
		Expr $expr,
		GeneratorScope $scope,
		ExpressionContext $context,
		?callable $alternativeNodeCallback,
	): Generator
	{
		$exprResult = yield new ExprAnalysisRequest($stmt, $expr->expr, $scope, $context->enterDeep(), $alternativeNodeCallback);
		$specifiedExprResult = yield new ExprAnalysisRequest($stmt, new Expr\BinaryOp\Equal($expr->expr, new Expr\ConstFetch(new FullyQualified('true'))), $scope, $context->enterDeep(), new NoopNodeCallback());

		return new ExprAnalysisResult(
			$exprResult->type->toBoolean(),
			$exprResult->nativeType->toBoolean(),
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			specifiedTruthyTypes: $specifiedExprResult->specifiedTruthyTypes,
			specifiedFalseyTypes: $specifiedExprResult->specifiedFalseyTypes,
			specifiedNullTypes: $specifiedExprResult->specifiedNullTypes,
		);
	}

}
