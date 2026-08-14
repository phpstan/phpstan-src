<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<FunctionCallableNode>
 */
#[AutowiredService]
final class FunctionCallableNodeHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof FunctionCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$throwPoints = [];
		$impurePoints = [];
		$hasYield = false;
		$isAlwaysTerminating = false;
		$nameResult = null;
		if ($expr->getName() instanceof Expr) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $nameResult->getScope();
			$hasYield = $nameResult->hasYield();
			$throwPoints = $nameResult->getThrowPoints();
			$impurePoints = $nameResult->getImpurePoints();
			$isAlwaysTerminating = $nameResult->isAlwaysTerminating();
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->resolveType($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr, $nameResult),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

	private function resolveType(MutatingScope $scope, FunctionCallableNode $expr, ?ExpressionResult $nameResult): Type
	{
		$originalNode = $expr->getOriginalNode();
		if ($originalNode->name instanceof Expr) {
			// $originalNode->name is the same node as $expr->getName(), processed
			// in processExpr exactly in this branch - read its ExpressionResult
			if ($nameResult === null) {
				throw new ShouldNotHappenException();
			}
			$callableType = $nameResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
			if (!$callableType->isCallable()->yes()) {
				return new ObjectType(Closure::class);
			}

			return $this->initializerExprTypeResolver->createFirstClassCallable(
				null,
				$callableType->getCallableParametersAcceptors($scope),
				$scope->nativeTypesPromoted,
			);
		}

		return $this->initializerExprTypeResolver->getFirstClassCallableType($originalNode, InitializerExprContext::fromScope($scope), $scope->nativeTypesPromoted);
	}

}
