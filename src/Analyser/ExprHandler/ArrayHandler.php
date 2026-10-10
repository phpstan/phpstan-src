<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar;
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
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
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
		$itemTypes = [];
		$variableFlows = [];
		$dependencies = [];
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
		$hasArrayRef = self::hasArrayReference($expr);
		$nextIndex = 0;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$itemCallbackScope = $scope;
			$keyResult = null;
			if ($arrayItem->key !== null) {
				$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
				if (!$arrayItem->key instanceof Scalar\String_ && !$arrayItem->key instanceof Scalar\Int_ && !$arrayItem->key instanceof Scalar\Float_) {
					$itemTypes[spl_object_id($arrayItem->key)] = [$keyResult->getType(), $keyResult->getNativeType()];
				}
				$keyDeps = $keyResult->getDependencies();
				if ($keyDeps !== null) {
					$dependencies[] = $keyDeps;
				}
				$keyFlow = $keyResult->getVariableFlow();
				if ($keyFlow !== null) {
					$variableFlows[] = $keyFlow;
				}
				$hasYield = $hasYield || $keyResult->hasYield();
				$keyThrow = $keyResult->getThrowPoints();
				if ($keyThrow !== []) {
					$throwPoints = array_merge($throwPoints, $keyThrow);
				}
				$keyImpure = $keyResult->getImpurePoints();
				if ($keyImpure !== []) {
					$impurePoints = array_merge($impurePoints, $keyImpure);
				}
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
			if (!$arrayItem->value instanceof Scalar\String_ && !$arrayItem->value instanceof Scalar\Int_ && !$arrayItem->value instanceof Scalar\Float_) {
				$itemTypes[spl_object_id($arrayItem->value)] = [$valueResult->getType(), $valueResult->getNativeType()];
			}
			$valDeps = $valueResult->getDependencies();
			if ($valDeps !== null) {
				$dependencies[] = $valDeps;
			}
			$valFlow = $valueResult->getVariableFlow();
			if ($valFlow !== null) {
				$variableFlows[] = $valFlow;
			}
			if ($arrayItem->byRef) {
				$variableFlows[] = VariableFlowBuilder::escapeRoot($arrayItem->value);
			}
			$hasYield = $hasYield || $valueResult->hasYield();
			$valThrow = $valueResult->getThrowPoints();
			if ($valThrow !== []) {
				$throwPoints = array_merge($throwPoints, $valThrow);
			}
			$valImpure = $valueResult->getImpurePoints();
			if ($valImpure !== []) {
				$impurePoints = array_merge($impurePoints, $valImpure);
			}
			$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
			$scope = $valueResult->getScope();
			// the item's callback fires after its key and value were processed,
			// with the item's entry scope - callback-side asks answer from the
			// storage instead of re-walking the yet-unstored sub-expressions
			$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $itemCallbackScope, $storage);

			if (!$hasArrayRef) {
				if ($arrayItem->key !== null) {
					$storage->removeExpressionResult($arrayItem->key);
				}
				$storage->removeExpressionResult($arrayItem->value);
			}
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);
		if ($nodeScopeResolver->observingTemplateArgumentFrame($scope) !== null) {
			$scope = $this->collectAbsorbedItems($expr, $itemTypes, $scope);
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
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $itemTypes, $beforeScope): Type {
				$type = $this->initializerExprTypeResolver->getArrayType($expr, static function (Expr $inner) use ($itemTypes, $nativeTypesPromoted): Type {
					if ($inner instanceof Scalar\String_) {
						return new ConstantStringType($inner->value);
					}
					if ($inner instanceof Scalar\Int_) {
						return new ConstantIntegerType($inner->value);
					}
					if ($inner instanceof Scalar\Float_) {
						return new ConstantFloatType($inner->value);
					}
					if ($inner instanceof TypeExpr) {
						return $inner->getExprType();
					}
					$id = spl_object_id($inner);
					if (array_key_exists($id, $itemTypes)) {
						return $nativeTypesPromoted
							? $itemTypes[$id][1]
							: $itemTypes[$id][0];
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
					if (
						$beforeScope->hasExpressionType($isCallableCall)->yes()
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

		$callableDeps = $this->getCallableDependencies($beforeScope, $expr, $itemTypes, $result);
		if ($callableDeps !== null) {
			$dependencies[] = $callableDeps;
		}

		return $result->withDependencies(Dependencies::merge(...$dependencies));
	}

	/**
	 * An array that may be a callable - `[Foo::class, 'method']` - depends on what calling it returns.
	 *
	 * @param array<int, array{Type, Type}> $itemTypes
	 */
	private function getCallableDependencies(MutatingScope $scope, Array_ $expr, array $itemTypes, ExpressionResult $result): ?Dependencies
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
		$firstItemType = null;
		if ($firstItemValue instanceof Scalar\String_) {
			$firstItemType = new ConstantStringType($firstItemValue->value);
		} elseif ($firstItemValue instanceof TypeExpr) {
			$firstItemType = $firstItemValue->getExprType();
		} elseif (isset($itemTypes[spl_object_id($firstItemValue)])) {
			$firstItemType = $itemTypes[spl_object_id($firstItemValue)][0];
		}

		if ($firstItemType === null || !$firstItemType->isClassString()->yes()) {
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
	 * @param array<int, array{Type, Type}> $itemTypes
	 */
	private function collectAbsorbedItems(Array_ $expr, array $itemTypes, MutatingScope $scope): MutatingScope
	{
		$absorbedItemTypes = [];
		foreach ($expr->items as $arrayItem) {
			if ($arrayItem->value instanceof Scalar\String_ || $arrayItem->value instanceof Scalar\Int_ || $arrayItem->value instanceof Scalar\Float_) {
				continue;
			}
			$id = spl_object_id($arrayItem->value);
			if (!isset($itemTypes[$id])) {
				continue;
			}
			$itemType = $itemTypes[$id][0];
			if (!ClosureSignatureInference::hasMarkers($itemType)) {
				continue;
			}
			$absorbedItemTypes[] = $itemType;
		}
		if ($absorbedItemTypes === []) {
			return $scope;
		}

		$arrayType = $this->initializerExprTypeResolver->getArrayType($expr, static function (Expr $inner) use ($itemTypes): Type {
			if ($inner instanceof Scalar\String_) {
				return new ConstantStringType($inner->value);
			}
			if ($inner instanceof Scalar\Int_) {
				return new ConstantIntegerType($inner->value);
			}
			if ($inner instanceof Scalar\Float_) {
				return new ConstantFloatType($inner->value);
			}
			if ($inner instanceof TypeExpr) {
				return $inner->getExprType();
			}
			$id = spl_object_id($inner);
			if (array_key_exists($id, $itemTypes)) {
				return $itemTypes[$id][0];
			}

			throw new ShouldNotHappenException();
		});

		return $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectAbsorbedInto($absorbedItemTypes, $arrayType));
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

	private static function hasArrayReference(Expr\Array_ $array): bool
	{
		foreach ($array->items as $item) {
			if ($item->byRef || ($item->value instanceof Expr\Array_ && self::hasArrayReference($item->value))) {
				return true;
			}
		}

		return false;
	}

}
