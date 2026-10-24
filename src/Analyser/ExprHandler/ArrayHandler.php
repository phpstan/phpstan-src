<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure as PhpClosure;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
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
use function in_array;
use function is_int;
use function is_string;
use function max;
use function spl_object_id;
use const PHP_INT_MAX;

/**
 * @implements ExprHandler<Array_>
 * @phpstan-type ArrayItemWalk array{arrayItem: ArrayItem, entryScope: MutatingScope, valueScope: MutatingScope, keyResult: ExpressionResult|null, valueResult: ExpressionResult|null, nested: mixed, itemWrite: VariableWrite|null}
 * @phpstan-type ArrayWalk array{expr: Array_, beforeScope: MutatingScope, scope: MutatingScope, context: ExpressionContext, items: list<ArrayItemWalk>, literalWrite: VariableWrite|null}
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ArrayHandler.cpp')]
final class ArrayHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private ClosureTypeResolver $closureTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Array_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$passedToTypeResolver = $context->getPassedToTypeResolver();
		if ($passedToTypeResolver === null || !self::hasClosureItems($expr)) {
			return $this->createResult($nodeScopeResolver, $this->walkItems($nodeScopeResolver, $stmt, $expr, $scope, $storage, $nodeCallback, $context, $context->getPassedToType(), $context->getNativePassedToType(), null), $storage, $nodeCallback);
		}

		// the closures in the literal are typed from the parameter the call
		// resolves for the literal - priced from the other items, walked first,
		// and the closures' declared signatures
		$resolve = fn (array $walk): array => $passedToTypeResolver($this->getWalkedArrayType($nodeScopeResolver, $walk));
		$walk = $this->walkItems($nodeScopeResolver, $stmt, $expr, $scope, $storage, $nodeCallback, $context, null, null, $resolve);
		[$passedToType, $nativePassedToType] = $resolve($walk);

		return $this->createResult($nodeScopeResolver, $this->completeWalk($nodeScopeResolver, $stmt, $walk, $storage, $nodeCallback, $passedToType, $nativePassedToType), $storage, $nodeCallback);
	}

	/**
	 * Walks the items in evaluation order. With $resolve, a closure / arrow
	 * function item and the closures of a nested literal are left for
	 * completeWalk(): the expected type they are walked with depends on the
	 * types of the items walked here. Creating a closure changes nothing in the
	 * scope but its template argument constraints - except a by-reference use,
	 * which binds the variable where the closure is created: such a closure is
	 * walked in place, with the expected type $resolve prices from the items
	 * walked before it.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @param (PhpClosure(ArrayWalk): array{?Type, ?Type})|null $resolve
	 * @return ArrayWalk
	 */
	private function walkItems(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Array_ $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context, ?Type $passedToType, ?Type $nativePassedToType, ?PhpClosure $resolve): array
	{
		$walk = [
			'expr' => $expr,
			'beforeScope' => $scope,
			'scope' => $scope,
			'context' => $context,
			'items' => [],
			'literalWrite' => null,
		];
		$literalWrite = $context->isValueFlowDirect() ? $context->getValueFlowTarget() : null;
		if ($literalWrite !== null && !$literalWrite->isOffsetWrite()) {
			$walk['literalWrite'] = $literalWrite;
		}
		$passedToType = $this->getExpectedArrayType($passedToType);
		$nativePassedToType = $this->getExpectedArrayType($nativePassedToType);
		$tracksKeys = $walk['literalWrite'] !== null || $passedToType !== null || $nativePassedToType !== null || $resolve !== null;
		$nextIndex = 0;
		foreach ($expr->items as $arrayItem) {
			$item = [
				'arrayItem' => $arrayItem,
				'entryScope' => $scope,
				'keyResult' => null,
				'valueResult' => null,
				'nested' => null,
				'itemWrite' => null,
			];
			$keyFirst = self::isKeyWalkedFirst($arrayItem);
			if ($arrayItem->key !== null && $keyFirst) {
				$item['keyResult'] = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
				$scope = $item['keyResult']->getScope();
			}
			$item['valueScope'] = $scope;

			$keyType = null;
			if ($tracksKeys) {
				[$keyType, $offset] = $this->getKeyTypeAndOffset($nodeScopeResolver, $arrayItem, $item['keyResult'], $scope, $nextIndex);
				if ($walk['literalWrite'] !== null) {
					$item['itemWrite'] = new VariableWrite($walk['literalWrite']->getVariableName(), $arrayItem, spl_object_id($arrayItem), VariableWrite::KIND_ARRAY_LITERAL_ITEM, true, $offset, $walk['literalWrite']->getId());
				}
			}

			$deferred = false;
			$itemPassedToType = $passedToType;
			$itemNativePassedToType = $nativePassedToType;
			if ($resolve !== null && !$arrayItem->unpack) {
				if ($arrayItem->value instanceof Array_ && self::hasClosureItems($arrayItem->value)) {
					$item['nested'] = $this->walkItems(
						$nodeScopeResolver,
						$stmt,
						$arrayItem->value,
						$scope,
						$storage,
						$nodeCallback,
						$this->getValueContext($context, $item, null, null, null),
						null,
						null,
						function (array $nestedWalk) use ($resolve, $walk, $item, $keyType): array {
							$item['nested'] = $nestedWalk;
							$walk['items'][] = $item;
							[$passedToType, $nativePassedToType] = $resolve($walk);
							$keyType = $keyType->toArrayKey();

							return [
								$this->getExpectedValueType($this->getExpectedArrayType($passedToType), $keyType),
								$this->getExpectedValueType($this->getExpectedArrayType($nativePassedToType), $keyType),
							];
						},
					);
					$scope = $item['nested']['scope'];
					$deferred = true;
				} elseif ($arrayItem->value instanceof ArrowFunction || ($arrayItem->value instanceof Closure && !self::hasByRefUse($arrayItem->value))) {
					$deferred = true;
				} elseif ($arrayItem->value instanceof Closure) {
					$walkSoFar = $walk;
					$walkSoFar['items'][] = $item;
					[$itemPassedToType, $itemNativePassedToType] = $resolve($walkSoFar);
					$itemPassedToType = $this->getExpectedArrayType($itemPassedToType);
					$itemNativePassedToType = $this->getExpectedArrayType($itemNativePassedToType);
				}
			}

			if (!$deferred) {
				$item['valueResult'] = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $this->getValueContext($context, $item, $itemPassedToType, $itemNativePassedToType, $keyType));
				$scope = $item['valueResult']->getScope();
			}

			if ($arrayItem->key !== null && !$keyFirst) {
				$item['keyResult'] = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
				$scope = $item['keyResult']->getScope();
			}

			if (!$deferred) {
				// the item's callback fires after its key and value were processed,
				// with the item's entry scope - callback-side asks answer from the
				// storage instead of re-walking the yet-unstored sub-expressions
				$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $item['entryScope'], $storage);
			}
			$walk['items'][] = $item;
			$walk['scope'] = $scope;
		}

		return $walk;
	}

	/**
	 * Walks the closures walkItems() deferred, each on the scope at its own
	 * position, with the expected types resolved from the walked items; their
	 * template argument constraints are carried over to the literal's scope.
	 *
	 * @param ArrayWalk $walk
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @return ArrayWalk
	 */
	private function completeWalk(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, array $walk, ExpressionResultStorage $storage, callable $nodeCallback, ?Type $passedToType, ?Type $nativePassedToType): array
	{
		$passedToType = $this->getExpectedArrayType($passedToType);
		$nativePassedToType = $this->getExpectedArrayType($nativePassedToType);
		$context = $walk['context'];
		$nextIndex = 0;
		foreach ($walk['items'] as $i => $item) {
			$arrayItem = $item['arrayItem'];
			[$keyType] = $this->getKeyTypeAndOffset($nodeScopeResolver, $arrayItem, $item['keyResult'], $item['valueScope'], $nextIndex);
			if ($item['valueResult'] !== null) {
				continue;
			}

			$valueContext = $this->getValueContext($context, $item, $passedToType, $nativePassedToType, $keyType);
			if ($item['nested'] !== null) {
				$item['valueResult'] = $this->createResult(
					$nodeScopeResolver,
					$this->completeWalk($nodeScopeResolver, $stmt, $item['nested'], $storage, $nodeCallback, $valueContext->getPassedToType(), $valueContext->getNativePassedToType()),
					$storage,
					$nodeCallback,
				);
				// the nested literal is walked here instead of by processExprNode() -
				// store its result and fire its callback the same way
				$nodeScopeResolver->storeExpressionResult($storage, $arrayItem->value, $item['valueResult']);
				$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, $arrayItem->value, $item['valueScope'], $storage, $valueContext);
			} else {
				$item['valueResult'] = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $item['valueScope'], $storage, $nodeCallback, $valueContext);
			}
			$walk['scope'] = $walk['scope']->addTemplateArgumentConstraints($item['valueResult']->getScope()->getTemplateArgumentConstraints());
			$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $item['entryScope'], $storage);
			$walk['items'][$i] = $item;
		}

		return $walk;
	}

	/**
	 * The literal's type from the items walked so far. A deferred closure
	 * contributes its declared signature; an item not reached yet - when a
	 * closure with a by-reference use is resolved early - what does not depend
	 * on its position: a literal, a closure's declared signature, mixed for
	 * anything else.
	 *
	 * @param ArrayWalk $walk
	 */
	private function getWalkedArrayType(NodeScopeResolver $nodeScopeResolver, array $walk): Type
	{
		$types = [];
		foreach ($walk['items'] as $item) {
			$arrayItem = $item['arrayItem'];
			if ($arrayItem->key !== null) {
				// a plain variable key is walked after its value - read it from the scope meanwhile
				$types[spl_object_id($arrayItem->key)] = $item['keyResult'] !== null
					? $item['keyResult']->getType()
					: ($nodeScopeResolver->findScopeStateType($arrayItem->key, $item['valueScope']) ?? new MixedType());
			}

			if ($item['valueResult'] !== null) {
				$types[spl_object_id($arrayItem->value)] = $item['valueResult']->getType();
			} elseif ($item['nested'] !== null) {
				$types[spl_object_id($arrayItem->value)] = $this->getWalkedArrayType($nodeScopeResolver, $item['nested']);
			}
		}

		$scope = $walk['scope'];
		$getType = function (Expr $inner) use (&$getType, $types, $nodeScopeResolver, $scope): Type {
			$id = spl_object_id($inner);
			if (array_key_exists($id, $types)) {
				return $types[$id];
			}

			if ($inner instanceof Closure || $inner instanceof ArrowFunction) {
				return $this->closureTypeResolver->getDeclaredClosureType($scope, $inner);
			}

			if ($inner instanceof Array_) {
				return $this->initializerExprTypeResolver->getArrayType($inner, $getType);
			}

			if ($inner instanceof Scalar\String_ || $inner instanceof Scalar\Int_ || $inner instanceof Scalar\Float_) {
				return $nodeScopeResolver->findScopeStateType($inner, $scope) ?? new MixedType();
			}

			return new MixedType();
		};

		return $this->initializerExprTypeResolver->getArrayType($walk['expr'], $getType);
	}

	/**
	 * @param ArrayWalk $walk
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function createResult(NodeScopeResolver $nodeScopeResolver, array $walk, ExpressionResultStorage $storage, callable $nodeCallback): ExpressionResult
	{
		$expr = $walk['expr'];
		$beforeScope = $walk['beforeScope'];
		$scope = $walk['scope'];
		$itemNodes = [];
		$itemResults = [];
		$variableFlows = [];
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($walk['items'] as $item) {
			$arrayItem = $item['arrayItem'];
			$keyResult = $item['keyResult'];
			$valueResult = $item['valueResult'];
			if ($valueResult === null) {
				throw new ShouldNotHappenException();
			}

			$itemNodes[] = new LiteralArrayItem($item['entryScope'], $arrayItem);
			$keyFirst = self::isKeyWalkedFirst($arrayItem);
			$walkedResults = [];
			if ($arrayItem->key !== null && $keyResult !== null) {
				$itemResults[spl_object_id($arrayItem->key)] = $keyResult;
				if ($keyFirst) {
					$walkedResults[] = $keyResult;
				}
			}
			$itemResults[spl_object_id($arrayItem->value)] = $valueResult;
			$walkedResults[] = $valueResult;
			if ($keyResult !== null && !$keyFirst) {
				$walkedResults[] = $keyResult;
			}

			foreach ($walkedResults as $walkedResult) {
				if ($walkedResult === $valueResult && $item['itemWrite'] !== null) {
					$variableFlows[] = VariableFlow::write($item['itemWrite']);
				}
				$variableFlows[] = $walkedResult->getVariableFlow();
				$hasYield = $hasYield || $walkedResult->hasYield();
				$throwPoints = array_merge($throwPoints, $walkedResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $walkedResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $walkedResult->isAlwaysTerminating();
			}
			if (!$arrayItem->byRef) {
				continue;
			}

			$variableFlows[] = VariableFlowBuilder::escapeRoot($arrayItem->value);
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);
		if ($nodeScopeResolver->observingTemplateArgumentFrame($scope) !== null) {
			$scope = $this->collectAbsorbedItems($expr, $itemResults, $scope);
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
			$this->getCallableDependencies($beforeScope, $expr, $itemResults, $result),
			...array_map(static fn (ExpressionResult $itemResult): ?Dependencies => $itemResult->getDependencies(), array_values($itemResults)),
		));
	}

	/**
	 * @param ArrayItemWalk $item
	 */
	private function getValueContext(ExpressionContext $context, array $item, ?Type $passedToType, ?Type $nativePassedToType, ?Type $keyType): ExpressionContext
	{
		$arrayItem = $item['arrayItem'];
		$valueContext = $item['itemWrite'] !== null
			? $context->enterDeep()->enterValueFlow($item['itemWrite'], false)
			: $context->enterDeepKeepingValueFlow();
		if (
			$keyType !== null
			&& ($passedToType !== null || $nativePassedToType !== null)
			&& !$arrayItem->unpack
			&& ($arrayItem->value instanceof Array_ || $arrayItem->value instanceof Closure || $arrayItem->value instanceof ArrowFunction)
		) {
			$keyType = $keyType->toArrayKey();
			$valueContext = $valueContext->enterPassedToType(
				$this->getExpectedValueType($passedToType, $keyType),
				$this->getExpectedValueType($nativePassedToType, $keyType),
			);
		}

		return $valueContext;
	}

	/**
	 * The type of the item's key and the offset its value is written to,
	 * advancing the next implicit index - unknown past PHP_INT_MAX, where PHP
	 * cannot append. A plain variable key not walked yet is read from the scope.
	 *
	 * @return array{Type, int|string|null}
	 */
	private function getKeyTypeAndOffset(NodeScopeResolver $nodeScopeResolver, ArrayItem $arrayItem, ?ExpressionResult $keyResult, MutatingScope $scope, ?int &$nextIndex): array
	{
		if ($arrayItem->unpack) {
			$nextIndex = null;
			return [new IntegerType(), null];
		}

		if ($arrayItem->key === null) {
			$keyType = $nextIndex !== null ? new ConstantIntegerType($nextIndex) : new IntegerType();
			$offset = $nextIndex;
			if ($nextIndex !== null) {
				$nextIndex = $nextIndex === PHP_INT_MAX ? null : $nextIndex + 1;
			}
			return [$keyType, $offset];
		}

		$writtenKeyType = $keyResult !== null
			? $keyResult->getType()
			: ($nodeScopeResolver->findScopeStateType($arrayItem->key, $scope) ?? new MixedType());
		$offset = VariableWriteOffset::fromType($writtenKeyType);
		if ($offset === null) {
			$nextIndex = null;
		} elseif (is_int($offset) && $nextIndex !== null) {
			$nextIndex = $offset === PHP_INT_MAX ? null : max($nextIndex, $offset + 1);
		}

		return [$writtenKeyType, $offset];
	}

	/** A plain variable key is read when PHP adds the item - after its value is evaluated. */
	private static function isKeyWalkedFirst(ArrayItem $arrayItem): bool
	{
		$key = $arrayItem->key;
		if ($key === null) {
			return false;
		}

		return !$key instanceof Expr\Variable
			|| !is_string($key->name)
			|| $key->name === 'this'
			|| in_array($key->name, Scope::SUPERGLOBAL_VARIABLES, true);
	}

	private static function hasClosureItems(Array_ $expr): bool
	{
		foreach ($expr->items as $arrayItem) {
			if ($arrayItem->unpack) {
				continue;
			}
			if (
				$arrayItem->value instanceof Closure
				|| $arrayItem->value instanceof ArrowFunction
				|| ($arrayItem->value instanceof Array_ && self::hasClosureItems($arrayItem->value))
			) {
				return true;
			}
		}

		return false;
	}

	private static function hasByRefUse(Closure $closure): bool
	{
		foreach ($closure->uses as $use) {
			if ($use->byRef) {
				return true;
			}
		}

		return false;
	}

	/**
	 * An array that may be a callable - `[Foo::class, 'method']` - depends on what calling it returns.
	 *
	 * @param array<int, ExpressionResult> $itemResults
	 */
	private function getCallableDependencies(MutatingScope $scope, Array_ $expr, array $itemResults, ExpressionResult $result): ?Dependencies
	{
		if (count($expr->items) !== 2 || !isset($expr->items[0], $expr->items[1])) {
			return null;
		}

		if ($scope->getFunctionCallStack() === []) {
			return null;
		}

		if (
			!$expr->items[0]->value instanceof ClassConstFetch
			|| !$expr->items[0]->value->name instanceof Identifier
			|| $expr->items[0]->value->name->toLowerString() !== 'class'
		) {
			return null;
		}

		$firstItemResult = $itemResults[spl_object_id($expr->items[0]->value)] ?? null;
		if ($firstItemResult === null || !$firstItemResult->getType()->isClassString()->yes()) {
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
	 */
	private function collectAbsorbedItems(Array_ $expr, array $itemResults, MutatingScope $scope): MutatingScope
	{
		$itemTypes = [];
		foreach ($expr->items as $arrayItem) {
			$itemType = $itemResults[spl_object_id($arrayItem->value)]->getType();
			if (!ClosureSignatureInference::hasMarkers($itemType)) {
				continue;
			}
			$itemTypes[] = $itemType;
		}
		if ($itemTypes === []) {
			return $scope;
		}

		$arrayType = $this->initializerExprTypeResolver->getArrayType($expr, static fn (Expr $inner): Type => $itemResults[spl_object_id($inner)]->getType());

		return $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectAbsorbedInto($itemTypes, $arrayType));
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
