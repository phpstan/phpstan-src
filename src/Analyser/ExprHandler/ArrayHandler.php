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
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\CallableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_merge;
use function count;
use function is_int;
use function max;
use function spl_object_id;

/**
 * @implements ExprHandler<Array_>
 */
#[AutowiredService]
final class ArrayHandler implements ExprHandler
{

	/** Offsets of a literal array tracked as separate writes of the assigned variable. */
	private const TRACKED_LITERAL_ITEMS_LIMIT = 32;

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
		// a literal assigned straight to a variable writes each of its constant
		// offsets: the items become offset writes of that variable, so a read of
		// one offset leaves the others unused
		$literalWrite = $context->getValueFlowTarget();
		if ($literalWrite !== null && (!$context->isValueFlowDirect() || $literalWrite->isOffsetWrite())) {
			$literalWrite = null;
		}
		$nextIndex = 0;
		$nextIndexKnown = true;
		$trackedItems = 0;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$itemCallbackScope = $scope;
			$keyResult = null;
			if ($arrayItem->key !== null) {
				$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
				$itemResults[spl_object_id($arrayItem->key)] = $keyResult;
				$hasYield = $hasYield || $keyResult->hasYield();
				$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
				$scope = $keyResult->getScope();
			}

			$valueContext = $context->enterDeepKeepingValueFlow();
			if ($literalWrite !== null) {
				if ($arrayItem->unpack) {
					$itemOffset = null;
					$nextIndexKnown = false;
				} elseif ($keyResult === null) {
					$itemOffset = $nextIndexKnown ? $nextIndex++ : null;
				} else {
					$itemOffset = VariableWriteOffset::fromType($keyResult->getType());
					if (is_int($itemOffset)) {
						$nextIndex = max($nextIndex, $itemOffset + 1);
					} elseif ($itemOffset === null) {
						$nextIndexKnown = false;
					}
				}
				if ($trackedItems < self::TRACKED_LITERAL_ITEMS_LIMIT) {
					$trackedItems++;
					$itemWrite = $nodeScopeResolver->recordVariableOffsetWrite($arrayItem, $literalWrite->getVariableName(), VariableWrite::KIND_ARRAY_LITERAL_ITEM, $itemOffset, $literalWrite);
					if ($itemWrite !== null) {
						$valueContext = $context->enterDeep()->enterValueFlow($itemWrite, false);
					}
				}
			}
			$valueResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $valueContext);
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
