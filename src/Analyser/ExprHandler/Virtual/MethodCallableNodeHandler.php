<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
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
use PHPStan\Node\MethodCallableNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<MethodCallableNode>
 */
#[AutowiredService]
final class MethodCallableNodeHandler implements ExprHandler
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
		return $expr instanceof MethodCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
		$scope = $varResult->getScope();
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();
		$isAlwaysTerminating = false;
		if ($expr->getName() instanceof Expr) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $nameResult->getScope();
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
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
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->resolveType($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr, $varResult),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

	private function resolveType(MutatingScope $scope, MethodCallableNode $expr, ExpressionResult $varResult): Type
	{
		$originalNode = $expr->getOriginalNode();
		if (!$originalNode->name instanceof Identifier) {
			return new ObjectType(Closure::class);
		}

		// $originalNode->var is the same node as $expr->getVar(), processed in
		// processExpr - read its ExpressionResult instead of Scope::getType()
		$varType = $varResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		$method = $scope->getMethodReflection($varType, $originalNode->name->toString());
		if ($method === null) {
			return new ObjectType(Closure::class);
		}

		return $this->initializerExprTypeResolver->createFirstClassCallable(
			$method,
			$method->getVariants(),
			$scope->nativeTypesPromoted,
		);
	}

}
