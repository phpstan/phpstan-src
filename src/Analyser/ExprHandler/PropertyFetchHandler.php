<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use function array_merge;

/**
 * @implements ExprHandler<PropertyFetch>
 */
#[AutowiredService]
final class PropertyFetchHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PropertyFetch;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$scopeBeforeVar = $scope;
		$result = $this->nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$hasYield = $result->hasYield();
		$throwPoints = $result->getThrowPoints();
		$impurePoints = $result->getImpurePoints();
		$isAlwaysTerminating = $result->isAlwaysTerminating();
		$scope = $result->getScope();
		if ($expr->name instanceof Expr) {
			$result = $this->nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();
			if ($this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			}
		} else {
			$propertyName = $expr->name->toString();
			$propertyHolderType = $scopeBeforeVar->getType($expr->var);
			$propertyReflection = $scopeBeforeVar->getInstancePropertyReflection($propertyHolderType, $propertyName);
			if ($propertyReflection !== null && $this->phpVersion->supportsPropertyHooks()) {
				$propertyDeclaringClass = $propertyReflection->getDeclaringClass();
				if ($propertyDeclaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $propertyDeclaringClass->getNativeProperty($propertyName);
					$throwPoints = array_merge($throwPoints, $this->nodeScopeResolver->getThrowPointsFromPropertyHook($scopeBeforeVar, $expr, $nativeProperty, 'get'));
				}
			}
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
