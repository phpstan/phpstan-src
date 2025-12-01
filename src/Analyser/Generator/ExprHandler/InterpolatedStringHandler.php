<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisRequest;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use function array_merge;

/**
 * @implements ExprHandler<InterpolatedString>
 */
#[AutowiredService]
final class InterpolatedStringHandler implements ExprHandler
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof InterpolatedString;
	}

	public function analyseExpr(
		Stmt $stmt,
		Expr $expr,
		GeneratorScope $scope,
		ExpressionContext $context,
		?callable $alternativeNodeCallback,
	): Generator
	{
		yield from [];

		$resultType = null;
		$resultNativeType = null;

		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($expr->parts as $part) {
			if ($part instanceof InterpolatedStringPart) {
				$partType = new ConstantStringType($part->value);
				$partNativeType = $partType;
			} else {
				$result = yield new ExprAnalysisRequest($stmt, $part, $scope, $context->enterDeep(), $alternativeNodeCallback);
				$partType = $result->type->toString();
				$partNativeType = $result->nativeType->toString();

				$hasYield = $hasYield || $result->hasYield;
				$throwPoints = array_merge($throwPoints, $result->throwPoints);
				$impurePoints = array_merge($impurePoints, $result->impurePoints);
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating;
				$scope = $result->scope;
			}

			if ($resultType === null) {
				$resultType = $partType;
				$resultNativeType = $partNativeType;
				continue;
			}

			if ($resultNativeType === null) {
				throw new ShouldNotHappenException();
			}

			$resultType = $this->initializerExprTypeResolver->resolveConcatType($resultType, $partType);
			$resultNativeType = $this->initializerExprTypeResolver->resolveConcatType($resultNativeType, $partNativeType);
		}

		return new ExprAnalysisResult(
			$resultType ?? new ConstantStringType(''),
			$resultNativeType ?? new ConstantStringType(''),
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			specifiedTruthyTypes: new SpecifiedTypes(),
			specifiedFalseyTypes: new SpecifiedTypes(),
			specifiedNullTypes: new SpecifiedTypes(),
		);
	}

}
