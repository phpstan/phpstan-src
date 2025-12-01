<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\Generator\NoopNodeCallback;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\IntegerRangeType;

/**
 * @implements ExprHandler<Expr\UnaryMinus>
 */
#[AutowiredService]
final class UnaryMinusHandler implements ExprHandler
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Expr\UnaryMinus;
	}

	public function analyseExpr(Stmt $stmt, Expr $expr, GeneratorScope $scope, ExpressionContext $context, ?callable $alternativeNodeCallback): Generator
	{
		$result = yield new ExprAnalysisRequest($stmt, $expr->expr, $scope, $context->enterDeep(), $alternativeNodeCallback);

		$type = $this->initializerExprTypeResolver->getUnaryMinusTypeFromType($expr->expr, $result->type);
		$nativeType = $this->initializerExprTypeResolver->getUnaryMinusTypeFromType($expr->expr, $result->nativeType);
		if ($type instanceof IntegerRangeType) {
			$mulResult = yield new ExprAnalysisRequest($stmt, new Expr\BinaryOp\Mul($expr, new Int_(-1)), $scope, $context->enterDeep(), new NoopNodeCallback());
			$type = $mulResult->type;
			$nativeType = $mulResult->nativeType;
		}

		return new ExprAnalysisResult(
			$type,
			$nativeType,
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
