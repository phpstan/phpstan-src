<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use DivisionByZeroError;
use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\Generator\InternalThrowPoint;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use function array_merge;

/**
 * @implements ExprHandler<Expr\BinaryOp\Mod>
 */
#[AutowiredService]
final class BinaryModHandler implements ExprHandler
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Expr\BinaryOp\Mod;
	}

	public function analyseExpr(Stmt $stmt, Expr $expr, GeneratorScope $scope, ExpressionContext $context, ?callable $alternativeNodeCallback): Generator
	{
		$leftResult = yield new ExprAnalysisRequest($stmt, $expr->left, $scope, $context, $alternativeNodeCallback);
		$rightResult = yield new ExprAnalysisRequest($stmt, $expr->right, $leftResult->scope, $context, $alternativeNodeCallback);

		$throwPoints = array_merge($leftResult->throwPoints, $rightResult->throwPoints);
		if (
			!$rightResult->type->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
		}

		return new ExprAnalysisResult(
			$this->initializerExprTypeResolver->getModTypeFromTypes($expr->left, $expr->right, $leftResult->type, $rightResult->type),
			$this->initializerExprTypeResolver->getModTypeFromTypes($expr->left, $expr->right, $leftResult->nativeType, $rightResult->nativeType),
			$rightResult->scope,
			hasYield: $leftResult->hasYield || $rightResult->hasYield,
			isAlwaysTerminating: $leftResult->isAlwaysTerminating || $rightResult->isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: array_merge($leftResult->impurePoints, $rightResult->impurePoints),
			specifiedTruthyTypes: new SpecifiedTypes(),
			specifiedFalseyTypes: new SpecifiedTypes(),
			specifiedNullTypes: new SpecifiedTypes(),
		);
	}

}
