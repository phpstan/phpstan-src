<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\MagicConst\Class_;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\MagicConst\Function_;
use PhpParser\Node\Scalar\MagicConst\Line;
use PhpParser\Node\Scalar\MagicConst\Method;
use PhpParser\Node\Scalar\MagicConst\Namespace_;
use PhpParser\Node\Scalar\MagicConst\Property;
use PhpParser\Node\Scalar\MagicConst\Trait_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\Generator\ExprAnalysisResult;
use PHPStan\Analyser\Generator\ExprHandler;
use PHPStan\Analyser\Generator\GeneratorScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;

/**
 * @implements ExprHandler<Dir|File|Line|Namespace_|Class_|Property|Function_|Method|Trait_>
 */
#[AutowiredService]
final class MagicConstHandler implements ExprHandler
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Dir
			|| $expr instanceof File
			|| $expr instanceof Line
			|| $expr instanceof Namespace_
			|| $expr instanceof Class_
			|| $expr instanceof Property
			|| $expr instanceof Function_
			|| $expr instanceof Method
			|| $expr instanceof Trait_;
	}

	public function analyseExpr(Stmt $stmt, Expr $expr, GeneratorScope $scope, ExpressionContext $context, ?callable $alternativeNodeCallback): Generator
	{
		yield from [];

		$initializerContext = InitializerExprContext::fromScope($scope);
		$type = $this->initializerExprTypeResolver->getType($expr, $initializerContext);

		return new ExprAnalysisResult(
			$type,
			$type,
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
