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
use PHPStan\Reflection\InitializerExprTypeResolver;

/**
 * @implements ExprHandler<Expr\BitwiseNot>
 */
#[AutowiredService]
final class BitwiseNotHandler implements ExprHandler
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Expr\BitwiseNot;
	}

	public function analyseExpr(Stmt $stmt, Expr $expr, GeneratorScope $scope, ExpressionContext $context, ?callable $alternativeNodeCallback): Generator
	{
		$result = yield new ExprAnalysisRequest($stmt, $expr->expr, $scope, $context->enterDeep(), $alternativeNodeCallback);

		return new ExprAnalysisResult(
			$this->initializerExprTypeResolver->getBitwiseNotTypeFromType($result->type),
			$this->initializerExprTypeResolver->getBitwiseNotTypeFromType($result->nativeType),
			$result->scope,
			hasYield: $result->hasYield,
			isAlwaysTerminating: $result->isAlwaysTerminating,
			throwPoints: $result->throwPoints,
			impurePoints: $result->impurePoints,
			specifiedTruthyTypes: new SpecifiedTypes(),
			specifiedFalseyTypes: new SpecifiedTypes(),
			specifiedNullTypes: new SpecifiedTypes(),
		);
	}

}
