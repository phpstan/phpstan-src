<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Generator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<Yield_>
 */
#[AutowiredService]
final class YieldHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Yield_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$throwPoints = [
			InternalThrowPoint::createImplicit($scope, $expr),
		];
		$impurePoints = [
			new ImpurePoint(
				$scope,
				$expr,
				'yield',
				'yield',
				true,
			),
		];
		$isAlwaysTerminating = false;
		if ($expr->key !== null) {
			$keyResult = $nodeScopeResolver->processExprNode($stmt, $expr->key, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $keyResult->getScope();
			$throwPoints = $keyResult->getThrowPoints();
			$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
			$isAlwaysTerminating = $keyResult->isAlwaysTerminating();
		}
		if ($expr->value !== null) {
			$valueResult = $nodeScopeResolver->processExprNode($stmt, $expr->value, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $valueResult->getScope();
			$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
		}

		// the enclosing function is lexical - the generator TSend type does not
		// vary with the scope the callback is later invoked on - resolve it once here.
		$functionReflection = $beforeScope->getFunction();

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: true,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: static function () use ($functionReflection): Type {
				if ($functionReflection === null) {
					return new MixedType();
				}

				$returnType = $functionReflection->getReturnType();
				$generatorSendType = $returnType->getTemplateType(Generator::class, 'TSend');
				if ($generatorSendType instanceof ErrorType) {
					return new MixedType();
				}

				return $generatorSendType;
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
