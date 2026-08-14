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
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
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
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Array_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$itemNodes = [];
		$itemResults = [];
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$itemCallbackScope = $scope;
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
			// the item's callback fires after its key and value were processed,
			// with the item's entry scope - callback-side asks answer from the
			// storage instead of re-walking the yet-unstored sub-expressions
			$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $itemCallbackScope, $storage);
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $itemResults, $beforeScope): Type {
				// each item type was captured at its own evaluation point in the
				// sequence - resolving all items on any single scope (the old world)
				// cannot handle items with side effects like [$b = 1, $b + 1, $b++]
				$type = $this->initializerExprTypeResolver->getArrayType($expr, static function (Expr $inner) use ($itemResults, $nativeTypesPromoted): Type {
					$id = spl_object_id($inner);
					if (array_key_exists($id, $itemResults)) {
						return $nativeTypesPromoted
							? $itemResults[$id]->getNativeType()
							: $itemResults[$id]->getType();
					}

					throw new ShouldNotHappenException();
				});

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
						$beforeScope->hasExpressionType($isCallableCall)->yes()
						// read the narrowed type from expressionTypes directly (the
						// synthetic is_callable() call was never processed as a child),
						// mirroring ConstFetchHandler's narrowed-constant lookup
						&& $beforeScope->expressionTypes[$beforeScope->getNodeKey($isCallableCall)]->getType()->isTrue()->yes()
					) {
						$type = TypeCombinator::intersect($type, new CallableType());
					}
				}

				return $type;
			},
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
		);
	}

}
