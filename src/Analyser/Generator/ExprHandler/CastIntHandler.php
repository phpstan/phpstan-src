<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\Generator\NoopNodeCallback;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<Int_>
 */
#[AutowiredService]
final class CastIntHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Int_;
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
		$specifiedExprResult = yield new ExprAnalysisRequest($stmt, new Expr\BinaryOp\Equal($expr->expr, new LNumber(0)), $scope, $context->enterDeep(), new NoopNodeCallback());

		return new ExprAnalysisResult(
			$exprResult->type->toInteger(),
			$exprResult->nativeType->toInteger(),
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
