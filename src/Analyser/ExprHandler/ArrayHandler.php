<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
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
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
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
		$variableFlows = [];
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$literalWrite = $context->isValueFlowDirect() ? $context->getValueFlowTarget() : null;
		if ($literalWrite !== null && $literalWrite->isOffsetWrite()) {
			$literalWrite = null;
		}
		$passedToType = $this->getExpectedArrayType($context->getPassedToType());
		$nativePassedToType = $this->getExpectedArrayType($context->getNativePassedToType());
		$hasExpectedType = $passedToType !== null || $nativePassedToType !== null;
		$nextIndex = 0;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$itemCallbackScope = $scope;
			$keyResult = null;
			if ($arrayItem->key !== null) {
				$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
				$itemResults[spl_object_id($arrayItem->key)] = $keyResult;
				$variableFlows[] = $keyResult->getVariableFlow();
				$hasYield = $hasYield || $keyResult->hasYield();
				$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
				$scope = $keyResult->getScope();
			}

			$valueContext = $context->enterDeepKeepingValueFlow();
			$keyType = null;
			if ($hasExpectedType && !$arrayItem->unpack && ($arrayItem->value instanceof Array_ || $arrayItem->value instanceof Closure || $arrayItem->value instanceof ArrowFunction)) {
				$keyType = $keyResult !== null ? $keyResult->getType()->toArrayKey() : ($nextIndex !== null ? new ConstantIntegerType($nextIndex) : new IntegerType());
			}
			if ($literalWrite !== null || $hasExpectedType) {
				if ($arrayItem->unpack) {
					$offset = null;
					$nextIndex = null;
				} elseif ($keyResult === null) {
					$offset = $nextIndex;
					if ($nextIndex !== null) {
						$nextIndex++;
					}
				} else {
					$offset = VariableWriteOffset::fromType($keyResult->getType());
					if ($offset === null) {
						$nextIndex = null;
					} elseif (is_int($offset) && $nextIndex !== null) {
						$nextIndex = max($nextIndex, $offset + 1);
					}
				}
			}
			if ($literalWrite !== null) {
				$itemWrite = new VariableWrite($literalWrite->getVariableName(), $arrayItem, spl_object_id($arrayItem), VariableWrite::KIND_ARRAY_LITERAL_ITEM, true, $offset, $literalWrite->getId());
				$variableFlows[] = VariableFlow::write($itemWrite);
				$valueContext = $context->enterDeep()->enterValueFlow($itemWrite, false);
			}
			if ($keyType !== null) {
				$valueContext = $valueContext->enterPassedToType(
					$this->getExpectedValueType($passedToType, $keyType),
					$this->getExpectedValueType($nativePassedToType, $keyType),
				);
			}
			$valueResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $valueContext);
			$itemResults[spl_object_id($arrayItem->value)] = $valueResult;
			$variableFlows[] = $valueResult->getVariableFlow();
			if ($arrayItem->byRef) {
				$variableFlows[] = VariableFlowBuilder::escapeRoot($arrayItem->value);
			}
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
			variableFlow: VariableFlow::sequence(...$variableFlows),
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

	private function getExpectedArrayType(?Type $type): ?Type
	{
		if ($type === null || $type->isIterable()->no()) {
			return null;
		}

		if ($type->isArray()->yes()) {
			return $type;
		}

		return TypeCombinator::intersect($type, new ArrayType(new MixedType(), new MixedType()));
	}

	private function getExpectedValueType(?Type $arrayType, Type $keyType): ?Type
	{
		if ($arrayType === null || $arrayType->hasOffsetValueType($keyType)->no()) {
			return null;
		}

		return $arrayType->getOffsetValueType($keyType);
	}

}
