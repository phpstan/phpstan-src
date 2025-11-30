<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<Expr\Cast\String_>
 */
#[AutowiredService]
final class CastStringHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Expr\Cast\String_;
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

		return new ExprAnalysisResult(
			$exprResult->type->toString(),
			$exprResult->nativeType->toString(),
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			specifiedTruthyTypes: new SpecifiedTypes(),
			specifiedFalseyTypes: new SpecifiedTypes(),
			specifiedNullTypes: new SpecifiedTypes(),
		);
	}

}
