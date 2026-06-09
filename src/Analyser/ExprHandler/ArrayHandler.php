<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\CallableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function count;

/**
 * @implements ExprHandler<Array_>
 */
#[AutowiredService]
final class ArrayHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Array_;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$type = $this->initializerExprTypeResolver->getArrayType($expr, static fn (Expr $expr): Type => $scope->getType($expr));

		if (
			count($expr->items) === 2
			&& isset($expr->items[0], $expr->items[1])
			&& $type->isCallable()->maybe()
		) {
			$isCallableCall = new FuncCall(
				new FullyQualified('is_callable'),
				[new Arg($expr)],
			);
			if (
				$scope->hasExpressionType($isCallableCall)->yes()
				&& $scope->getType($isCallableCall)->isTrue()->yes()
			) {
				$type = TypeCombinator::intersect($type, new CallableType());
			}
		}

		return $type;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$itemNodes = [];
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $scope, $storage);
			if ($arrayItem->key !== null) {
				$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $keyResult->hasYield();
				$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
				$scope = $keyResult->getScope();
			}

			$valueResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $valueResult->hasYield();
			$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
			$scope = $valueResult->getScope();
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
