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
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\CallableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_merge;
use function count;
use function spl_object_id;

/**
 * @implements ExprHandler<Array_>
 */
#[AutowiredService]
final class ArrayHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
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
		$itemResults = [];
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $scope, $storage);
			if ($arrayItem->key !== null) {
				$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeep());
				$itemResults[spl_object_id($arrayItem->key)] = $keyResult;
				$hasYield = $hasYield || $keyResult->hasYield();
				$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
				$scope = $keyResult->getScope();
			}

			$valueResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $context->enterDeep());
			$itemResults[spl_object_id($arrayItem->value)] = $valueResult;
			$hasYield = $hasYield || $valueResult->hasYield();
			$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
			$scope = $valueResult->getScope();
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);

		// each item type was captured at its own evaluation point in the sequence —
		// resolving them on any single scope (the old world) cannot handle items
		// with side effects like `[$b = 1, $b + 1, $b++]`
		$typeCallback = function (Expr $e, MutatingScope $s) use ($itemResults): Type {
			if (!$e instanceof Array_) {
				throw new ShouldNotHappenException();
			}

			$type = $this->initializerExprTypeResolver->getArrayType($e, static function (Expr $inner) use ($itemResults, $s): Type {
				$id = spl_object_id($inner);
				if (array_key_exists($id, $itemResults)) {
					return $itemResults[$id]->getTypeForScope($s);
				}

				// getArrayType only asks about item keys and values — guarded
				// legacy bridge just in case (PHPSTAN_FNSR=0)
				return $s->getType($inner);
			});

			if (
				count($e->items) === 2
				&& isset($e->items[0], $e->items[1])
				&& $type->isCallable()->maybe()
			) {
				$isCallableCall = new FuncCall(
					new FullyQualified('is_callable'),
					[new Arg($e)],
				);
				$isCallableCallString = $s->getNodeKey($isCallableCall);
				if (
					array_key_exists($isCallableCallString, $s->expressionTypes)
					&& $s->expressionTypes[$isCallableCallString]->getType()->isTrue()->yes()
				) {
					$type = TypeCombinator::intersect($type, new CallableType());
				}
			}

			return $type;
		};

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $typeCallback($e, $s), $ctx),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
