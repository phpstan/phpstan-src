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
use function array_merge;

/**
 * @implements ExprHandler<Expr\BinaryOp\Mul>
 */
#[AutowiredService]
final class BinaryMulHandler implements ExprHandler
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Expr\BinaryOp\Mul;
	}

	public function analyseExpr(Stmt $stmt, Expr $expr, GeneratorScope $scope, ExpressionContext $context, ?callable $alternativeNodeCallback): Generator
	{
		$leftResult = yield new ExprAnalysisRequest($stmt, $expr->left, $scope, $context, $alternativeNodeCallback);
		$rightResult = yield new ExprAnalysisRequest($stmt, $expr->right, $leftResult->scope, $context, $alternativeNodeCallback);

		return new ExprAnalysisResult(
			$this->initializerExprTypeResolver->getMulTypeFromTypes($expr->left, $expr->right, $leftResult->type, $rightResult->type),
			$this->initializerExprTypeResolver->getMulTypeFromTypes($expr->left, $expr->right, $leftResult->nativeType, $rightResult->nativeType),
			$rightResult->scope,
			hasYield: $leftResult->hasYield || $rightResult->hasYield,
			isAlwaysTerminating: $leftResult->isAlwaysTerminating || $rightResult->isAlwaysTerminating,
			throwPoints: array_merge($leftResult->throwPoints, $rightResult->throwPoints),
			impurePoints: array_merge($leftResult->impurePoints, $rightResult->impurePoints),
			specifiedTruthyTypes: new SpecifiedTypes(),
			specifiedFalseyTypes: new SpecifiedTypes(),
			specifiedNullTypes: new SpecifiedTypes(),
		);
	}

}
