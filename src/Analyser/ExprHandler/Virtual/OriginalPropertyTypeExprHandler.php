<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<OriginalPropertyTypeExpr>
 */
#[AutowiredService]
final class OriginalPropertyTypeExprHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private PropertyReflectionFinder $propertyReflectionFinder,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof OriginalPropertyTypeExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$propertyFetch = $expr->getPropertyFetch();
		if ($propertyFetch instanceof Expr\PropertyFetch) {
			$holderResult = $nodeScopeResolver->processExprNode($stmt, $propertyFetch->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		} else {
			$holderResult = $propertyFetch->class instanceof Expr
				? $nodeScopeResolver->processExprNode($stmt, $propertyFetch->class, $scope, $storage, $nodeCallback, $context->enterDeep())
				: null;
		}

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: function (Expr $expr, MutatingScope $scope) use ($propertyFetch, $holderResult): Type {
				if ($holderResult !== null) {
					$holderType = $holderResult->getTypeForScope($scope);
				} elseif ($propertyFetch instanceof Expr\StaticPropertyFetch && $propertyFetch->class instanceof Name) {
					$holderType = $scope->resolveTypeByName($propertyFetch->class);
				} else {
					return new ErrorType();
				}

				$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNodeWithTypes($propertyFetch, $scope, $holderType, null);
				if ($propertyReflection === null) {
					return new ErrorType();
				}

				return $propertyReflection->getReadableType();
			},
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr->getPropertyFetch(), $scope);
		if ($propertyReflection === null) {
			return new ErrorType();
		}

		return $propertyReflection->getReadableType();
	}

}
