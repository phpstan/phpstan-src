<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function is_int;
use function max;
use function spl_object_id;

/**
 * @implements ExprHandler<Array_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ArrayHandler.cpp')]
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
		$literalTypes = [];
		$literalNativeTypes = [];
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
		// AssignHandler reads the keys' results of an assigned array with a by-ref
		// item to alias its slots - only an array without one drops its literals'
		$keepsLiteralResults = self::hasByRefItem($expr);
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

			if ($keepsLiteralResults) {
				continue;
			}

			// a literal key or value is lexical - keep only its types and drop its
			// result, which a large literal array would otherwise hold twice per
			// item until the statement ends; asks about the literal from now on
			// are answered by the scope directly (MutatingScope::resolveType())
			foreach ([$arrayItem->key, $arrayItem->unpack ? null : $arrayItem->value] as $part) {
				if (!self::isLiteral($part)) {
					continue;
				}
				$id = spl_object_id($part);
				$literalTypes[$id] = $itemResults[$id]->getType();
				$literalNativeTypes[$id] = $itemResults[$id]->getNativeType();
				unset($itemResults[$id]);
				$storage->removeExpressionResult($part);
			}
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);
		if ($nodeScopeResolver->observingTemplateArgumentFrame($scope) !== null) {
			$scope = $this->collectAbsorbedItems($expr, $itemResults, $literalTypes, $scope);
		}

		$result = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: VariableFlow::sequence(...$variableFlows),
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $itemResults, $literalTypes, $literalNativeTypes, $beforeScope): Type {
				// each item type was captured at its own evaluation point in the
				// sequence - resolving all items on any single scope (the old world)
				// cannot handle items with side effects like [$b = 1, $b + 1, $b++]
				$type = $this->initializerExprTypeResolver->getArrayType($expr, static fn (Expr $inner): Type => $nativeTypesPromoted
					? self::getItemType($inner, $itemResults, $literalNativeTypes, true)
					: self::getItemType($inner, $itemResults, $literalTypes, false));

				if (
					count($expr->items) === 2
					&& isset($expr->items[0], $expr->items[1])
				) {
					$isCallableCall = new FuncCall(
						new FullyQualified('is_callable'),
						[new Arg($expr)],
					);
					// isCallable() is asked last - it reflects the class named by the
					// first item, which is expensive and unnecessary for arrays never
					// narrowed by is_callable()
					if (
						$beforeScope->hasExpressionType($isCallableCall)->yes()
						// read the narrowed type from expressionTypes directly (the
						// synthetic is_callable() call was never processed as a child),
						// mirroring ConstFetchHandler's narrowed-constant lookup
						&& $beforeScope->expressionTypes[$beforeScope->getNodeKey($isCallableCall)]->getType()->isTrue()->yes()
						&& $type->isCallable()->maybe()
					) {
						$type = TypeCombinator::intersect($type, new CallableType());
					}
				}

				return $type;
			},
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
		);

		return $result->withDependencies(Dependencies::merge(
			$this->getCallableDependencies($beforeScope, $expr, $itemResults, $literalTypes, $result),
			...array_map(static fn (ExpressionResult $itemResult): ?Dependencies => $itemResult->getDependencies(), array_values($itemResults)),
		));
	}

	/**
	 * An array that may be a callable - `[Foo::class, 'method']` - depends on what calling it returns.
	 *
	 * @param array<int, ExpressionResult> $itemResults
	 * @param array<int, Type> $literalTypes
	 */
	private function getCallableDependencies(MutatingScope $scope, Array_ $expr, array $itemResults, array $literalTypes, ExpressionResult $result): ?Dependencies
	{
		if (count($expr->items) !== 2 || !isset($expr->items[0])) {
			return null;
		}

		// a class constant, property default or enum case value is not called where it is
		// declared - testing it would reflect whatever class its first item happens to name
		if (
			$scope->isInClass()
			&& $scope->getFunction() === null
			&& !$scope->isInAnonymousFunction()
			&& $scope->getFunctionCallStack() === []
		) {
			return null;
		}

		$firstItemValue = $expr->items[0]->value;
		$id = spl_object_id($firstItemValue);
		if (!array_key_exists($id, $itemResults) && !array_key_exists($id, $literalTypes)) {
			return null;
		}
		if (!self::getItemType($firstItemValue, $itemResults, $literalTypes, false)->isClassString()->yes()) {
			return null;
		}

		$arrayType = $result->getType();
		if ($arrayType->isCallable()->no()) {
			return null;
		}

		$returnTypes = [];
		foreach ($arrayType->getCallableParametersAcceptors($scope) as $variant) {
			$returnTypes[] = $variant->getReturnType();
		}

		return Dependencies::create($scope->getFile(), $returnTypes);
	}

	/**
	 * A literal generalized by an unpacked item absorbs the closures of its items
	 * into a wider value type - see ClosureSignatureInference::collectAbsorbed().
	 *
	 * @param array<int, ExpressionResult> $itemResults
	 * @param array<int, Type> $literalTypes
	 */
	private function collectAbsorbedItems(Array_ $expr, array $itemResults, array $literalTypes, MutatingScope $scope): MutatingScope
	{
		$itemTypes = [];
		foreach ($expr->items as $arrayItem) {
			$itemType = self::getItemType($arrayItem->value, $itemResults, $literalTypes, false);
			if (!ClosureSignatureInference::hasMarkers($itemType)) {
				continue;
			}
			$itemTypes[] = $itemType;
		}
		if ($itemTypes === []) {
			return $scope;
		}

		$arrayType = $this->initializerExprTypeResolver->getArrayType($expr, static fn (Expr $inner): Type => self::getItemType($inner, $itemResults, $literalTypes, false));

		return $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectAbsorbedInto($itemTypes, $arrayType));
	}

	public static function hasByRefItem(Array_ $array): bool
	{
		foreach ($array->items as $item) {
			if ($item->byRef || ($item->value instanceof Array_ && self::hasByRefItem($item->value))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @phpstan-assert-if-true Scalar\String_|Scalar\Int_|Scalar\Float_ $expr
	 */
	private static function isLiteral(?Expr $expr): bool
	{
		return $expr instanceof Scalar\String_ || $expr instanceof Scalar\Int_ || $expr instanceof Scalar\Float_;
	}

	/**
	 * The type a key or value of the literal was processed to - from its result,
	 * or for a literal, whose result is dropped, from its kept type.
	 *
	 * @param array<int, ExpressionResult> $itemResults
	 * @param array<int, Type> $literalTypes the literals' types of the asked flavour
	 */
	private static function getItemType(Expr $item, array $itemResults, array $literalTypes, bool $nativeTypesPromoted): Type
	{
		$id = spl_object_id($item);
		if (array_key_exists($id, $literalTypes)) {
			return $literalTypes[$id];
		}
		if (array_key_exists($id, $itemResults)) {
			return $nativeTypesPromoted
				? $itemResults[$id]->getNativeType()
				: $itemResults[$id]->getType();
		}

		throw new ShouldNotHappenException();
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
