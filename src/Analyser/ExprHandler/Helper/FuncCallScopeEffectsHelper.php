<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgsResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_slice;
use function count;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * The scope-only side effects of a function call, applied after the call's
 * arguments were processed: by-ref array function results (array_pop(),
 * sort(), array_splice(), ...), invalidation of paired reads
 * (json_last_error(), file_get_contents()), possibly-impure value
 * remembering, output-buffer level tracking and volatile-expression
 * invalidation. Extracted from FuncCallHandler::processExpr() so the hot
 * per-call frame stays small; nothing here touches the call's own result
 * state (throw points, impure points, yield, termination).
 */
#[AutowiredService]
final class FuncCallScopeEffectsHelper
{

	public function __construct(
		private OutputBufferHelper $outputBufferHelper,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
	)
	{
	}

	/**
	 * @param array{Type, Type} $arrayWalkValueTypes
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function applyArrayWalkResult(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $arrayWalkArrayArg, array $arrayWalkValueTypes, ArgsResult $argsResult, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback): MutatingScope
	{
		$arrayWalkArrayArgResult = $argsResult->requireArgResult($arrayWalkArrayArg);
		$arrayWalkOriginalArrayType = $arrayWalkArrayArgResult->getTypeOnScope($scope, false);
		$arrayWalkOriginalArrayNativeType = $arrayWalkArrayArgResult->getTypeOnScope($scope, true);
		$arrayWalkValueType = $arrayWalkValueTypes[0];
		$arrayWalkValueNativeType = $arrayWalkValueTypes[1];
		$newArrayType = $arrayWalkOriginalArrayType->mapValueType(static fn (Type $type): Type => $arrayWalkValueType);
		$newArrayNativeType = $arrayWalkOriginalArrayNativeType->mapValueType(static fn (Type $type): Type => $arrayWalkValueNativeType);

		$scope = $nodeScopeResolver->processVirtualAssign(
			$scope,
			$storage,
			$stmt,
			$arrayWalkArrayArg,
			new NativeTypeExpr($newArrayType, $newArrayNativeType),
			$nodeCallback,
		)->getScope();

		return $scope;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function applyCallScopeEffects(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, FuncCall $normalizedExpr, ?FunctionReflection $functionReflection, ?ParametersAcceptor $parametersAcceptor, ArgsResult $argsResult, MutatingScope $scope, MutatingScope $scopeBeforeArgs, ExpressionResultStorage $storage, callable $nodeCallback): MutatingScope
	{
		if (
			$parametersAcceptor instanceof ClosureType && count($parametersAcceptor->getImpurePoints()) > 0
			&& $scope->isInClass()
		) {
			$scope = $scope->invalidateExpression(new Variable('this'), true);
		}

		if (
			$functionReflection !== null
			&& $parametersAcceptor !== null
			&& $this->rememberPossiblyImpureFunctionValues
			&& $functionReflection->hasSideEffects()->maybe()
			&& !$functionReflection->isBuiltin()
		) {
			$scope = $scope->assignExpression(
				new PossiblyImpureCallExpr($normalizedExpr, $normalizedExpr, sprintf('%s()', $functionReflection->getName())),
				$parametersAcceptor->getReturnType(),
				new MixedType(),
			);
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['json_encode', 'json_decode'], true)
		) {
			$scope = $scope->invalidateExpression(new FuncCall(new Name('json_last_error'), []))
				->invalidateExpression(new FuncCall(new Name\FullyQualified('json_last_error'), []))
				->invalidateExpression(new FuncCall(new Name('json_last_error_msg'), []))
				->invalidateExpression(new FuncCall(new Name\FullyQualified('json_last_error_msg'), []));
		}

		if (
			$functionReflection !== null
			&& $functionReflection->getName() === 'file_put_contents'
			&& count($normalizedExpr->getArgs()) > 0
		) {
			$scope = $scope->invalidateExpression(new FuncCall(new Name('file_get_contents'), [$normalizedExpr->getArgs()[0]]))
				->invalidateExpression(new FuncCall(new Name\FullyQualified('file_get_contents'), [$normalizedExpr->getArgs()[0]]));
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['array_pop', 'array_shift'], true)
			&& count($normalizedExpr->getArgs()) >= 1
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$arrayArgResult = $argsResult->requireArgResult($arrayArg);
			$arrayArgType = $arrayArgResult->getTypeOnScope($scope, false);
			$arrayArgNativeType = $arrayArgResult->getTypeOnScope($scope, true);
			$isArrayPop = $functionReflection->getName() === 'array_pop';

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr(
					$isArrayPop ? $arrayArgType->popArray() : $arrayArgType->shiftArray(),
					$isArrayPop ? $arrayArgNativeType->popArray() : $arrayArgNativeType->shiftArray(),
				),
				$nodeCallback,
			)->getScope();
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['array_push', 'array_unshift'], true)
			&& count($normalizedExpr->getArgs()) >= 2
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr(
					$this->getArrayFunctionAppendingType($functionReflection, $scopeBeforeArgs, $normalizedExpr, $argsResult),
					$this->getArrayFunctionAppendingType($functionReflection, $scopeBeforeArgs->doNotTreatPhpDocTypesAsCertain(), $normalizedExpr, $argsResult),
				),
				$nodeCallback,
			)->getScope();
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['fopen', 'file_get_contents'], true)
		) {
			$scope = $scope->assignVariable('http_response_header', new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()), new AccessoryArrayListType()]), new ArrayType(new IntegerType(), new StringType()), TrinaryLogic::createYes());
		}

		if (
			$functionReflection !== null
			&& $functionReflection->getName() === 'shuffle'
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr($argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope, false)->shuffleArray(), $argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope, true)->shuffleArray()),
				$nodeCallback,
			)->getScope();
		}

		if (
			$functionReflection !== null
			&& $functionReflection->getName() === 'array_splice'
			&& count($normalizedExpr->getArgs()) >= 2
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;
			$arrayArgResult = $argsResult->requireArgResult($arrayArg);
			$arrayArgType = $arrayArgResult->getType();
			$arrayArgNativeType = $arrayArgResult->getNativeType();

			$offsetArg = $normalizedExpr->getArgs()[1]->value;
			$offsetType = $argsResult->requireArgResult($offsetArg)->getType();

			if (isset($normalizedExpr->getArgs()[2])) {
				$lengthArg = $normalizedExpr->getArgs()[2]->value;
				$lengthType = $argsResult->requireArgResult($lengthArg)->getType();
			} else {
				$lengthType = new NullType();
			}

			if (isset($normalizedExpr->getArgs()[3])) {
				$replacementArg = $normalizedExpr->getArgs()[3]->value;
				$replacementArgResult = $argsResult->requireArgResult($replacementArg);
				$replacementType = $replacementArgResult->getType();
				$replacementNativeType = $replacementArgResult->getNativeType();
			} else {
				$replacementType = new ConstantArrayType([], []);
				$replacementNativeType = new ConstantArrayType([], []);
			}

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr(
					$arrayArgType->spliceArray($offsetType, $lengthType, $replacementType),
					$arrayArgNativeType->spliceArray($offsetType, $lengthType, $replacementNativeType),
				),
				$nodeCallback,
			)->getScope();
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['sort', 'rsort', 'usort'], true)
			&& count($normalizedExpr->getArgs()) >= 1
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr($argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope, false)->shuffleArray(), $argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope, true)->shuffleArray()),
				$nodeCallback,
			)->getScope();
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['natcasesort', 'natsort', 'arsort', 'asort', 'ksort', 'krsort', 'uasort', 'uksort'], true)
			&& count($normalizedExpr->getArgs()) >= 1
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr($argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope, false)->makeListMaybe(), $argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope, true)->makeListMaybe()),
				$nodeCallback,
			)->getScope();
		}

		if ($functionReflection !== null) {
			if ($functionReflection->getName() === 'compact') {
				$compactedNames = $this->findCompactVariableNames($normalizedExpr, $argsResult, $scope);
				if ($compactedNames === null) {
					$nodeScopeResolver->markAllReachingVariablesRead($scope);
				} else {
					foreach ($compactedNames as $compactedName) {
						$nodeScopeResolver->markVariableRead($compactedName, $scope);
					}
				}
			} elseif (in_array($functionReflection->getName(), ['get_defined_vars', 'extract'], true)) {
				$nodeScopeResolver->markAllReachingVariablesRead($scope);
			}
		}

		if (
			$functionReflection !== null
			&& $functionReflection->getName() === 'extract'
		) {
			$extractedArg = $normalizedExpr->getArgs()[0]->value;
			$extractedType = $argsResult->requireArgResult($extractedArg)->getTypeOnScope($scope, false);
			$constantArrays = $extractedType->getConstantArrays();
			if (count($constantArrays) > 0) {
				$properties = [];
				$optionalProperties = [];
				$refCount = [];
				foreach ($constantArrays as $constantArray) {
					foreach ($constantArray->getKeyTypes() as $i => $keyType) {
						if ($keyType->isString()->no()) {
							// integers as variable names not allowed
							continue;
						}
						$key = (string) $keyType->getValue();
						$valueType = $constantArray->getValueTypes()[$i];
						$optional = $constantArray->isOptionalKey($i);
						if ($optional) {
							$optionalProperties[] = $key;
						}
						if (isset($properties[$key])) {
							$properties[$key] = TypeCombinator::union($properties[$key], $valueType);
							$refCount[$key]++;
						} else {
							$properties[$key] = $valueType;
							$refCount[$key] = 1;
						}
					}
				}
				foreach ($properties as $name => $type) {
					$optional = in_array($name, $optionalProperties, true) || $refCount[$name] < count($constantArrays);

					if (!$optional) {
						$scope = $scope->assignVariable($name, $type, $type, TrinaryLogic::createYes());
					} else {
						$hasVariable = $scope->hasVariableType($name);
						if (!$hasVariable->no()) {
							$type = TypeCombinator::union($scope->getVariableType($name), $type);
						}

						$scope = $scope->assignVariable($name, $type, $type, $scope->hasVariableType($name)->or(TrinaryLogic::createMaybe()));
					}
				}
			} else {
				$scope = $scope->afterExtractCall();
			}
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['clearstatcache', 'unlink'], true)
		) {
			$scope = $scope->afterClearstatcacheCall();
		}

		if (
			$functionReflection !== null
			&& str_starts_with($functionReflection->getName(), 'openssl')
		) {
			$scope = $scope->afterOpenSslCall($functionReflection->getName());
		}

		$outputBufferDelta = $functionReflection !== null ? $this->outputBufferHelper->getLevelDelta($functionReflection->getName()) : 0;
		if ($outputBufferDelta !== 0) {
			$scope = $this->outputBufferHelper->applyLevelDelta($nodeScopeResolver, $scope, $outputBufferDelta);
		}

		$pureCallable = $parametersAcceptor instanceof CallableParametersAcceptor
			&& count($parametersAcceptor->getImpurePoints()) === 0;
		if (
			($functionReflection !== null && !$functionReflection->isBuiltin() && !$functionReflection->hasSideEffects()->no())
			|| ($functionReflection === null && !$pureCallable)
		) {
			$scope = $scope->invalidateVolatileExpressions();
		}
		return $scope;
	}

	private function getArrayFunctionAppendingType(FunctionReflection $functionReflection, Scope $scope, FuncCall $expr, ArgsResult $argsResult): Type
	{
		$arrayArg = $expr->getArgs()[0]->value;
		$arrayType = $argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope->toWalkScope(), $scope->toWalkScope()->nativeTypesPromoted);
		$callArgs = array_slice($expr->getArgs(), 1);

		/**
		 * @param Arg[] $callArgs
		 * @param callable(?Type, Type, bool): void $setOffsetValueType
		 */
		$setOffsetValueTypes = static function (Scope $scope, array $callArgs, callable $setOffsetValueType, ?bool &$nonConstantArrayWasUnpacked = null) use ($argsResult): void {
			foreach ($callArgs as $callArg) {
				$callArgType = $argsResult->requireArgResult($callArg->value)->getTypeOnScope($scope->toWalkScope(), $scope->toWalkScope()->nativeTypesPromoted);
				if ($callArg->unpack) {
					$constantArrays = $callArgType->getConstantArrays();
					if (count($constantArrays) === 1) {
						$iterableValueTypes = $constantArrays[0]->getValueTypes();
					} else {
						$iterableValueTypes = [$callArgType->getIterableValueType()];
						$nonConstantArrayWasUnpacked = true;
					}

					$isOptional = !$callArgType->isIterableAtLeastOnce()->yes();
					foreach ($iterableValueTypes as $iterableValueType) {
						if ($iterableValueType instanceof UnionType) {
							foreach ($iterableValueType->getTypes() as $innerType) {
								$setOffsetValueType(null, $innerType, $isOptional);
							}
						} else {
							$setOffsetValueType(null, $iterableValueType, $isOptional);
						}
					}
					continue;
				}
				$setOffsetValueType(null, $callArgType, false);
			}
		};

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$newArrayTypes = [];
			$prepend = $functionReflection->getName() === 'array_unshift';
			foreach ($constantArrays as $constantArray) {
				$arrayTypeBuilder = $prepend ? ConstantArrayTypeBuilder::createEmpty() : ConstantArrayTypeBuilder::createFromConstantArray($constantArray);

				$setOffsetValueTypes(
					$scope,
					$callArgs,
					static function (?Type $offsetType, Type $valueType, bool $optional) use (&$arrayTypeBuilder): void {
						$arrayTypeBuilder->setOffsetValueType($offsetType, $valueType, $optional);
					},
					$nonConstantArrayWasUnpacked,
				);

				if ($prepend) {
					$keyTypes = $constantArray->getKeyTypes();
					$valueTypes = $constantArray->getValueTypes();
					foreach ($keyTypes as $k => $keyType) {
						$arrayTypeBuilder->setOffsetValueType(
							count($keyType->getConstantStrings()) === 1 ? $keyType->getConstantStrings()[0] : null,
							$valueTypes[$k],
							$constantArray->isOptionalKey($k),
						);
					}

					$unsealedTypes = $constantArray->getUnsealedTypes();
					if ($unsealedTypes !== null) {
						$arrayTypeBuilder->makeUnsealed($unsealedTypes[0], $unsealedTypes[1]);
					}
				}

				$constantArray = $arrayTypeBuilder->getArray();

				if ($constantArray->isConstantArray()->yes() && $nonConstantArrayWasUnpacked) {
					$constantArrays = $constantArray->getConstantArrays();
					if ($constantArray->isList()->yes()) {
						// A list can't preserve precise indices when an
						// unknown number of values is prepended/appended —
						// every index would be shifted by an unknown
						// amount. Degrade to a `non-empty-list<...>` of
						// the value union.
						$array = new ArrayType($constantArray->generalize(GeneralizePrecision::lessSpecific())->getIterableKeyType(), $constantArray->getIterableValueType());
						$constantArray = $constantArray->isIterableAtLeastOnce()->yes()
							? new IntersectionType([$array, new NonEmptyArrayType()])
							: $array;
						$constantArray = TypeCombinator::intersect($constantArray, new AccessoryArrayListType());
					} elseif (count($constantArrays) === 1) {
						// Associative input — string keys keep their
						// precise values and the unknown count of
						// unpacked items lives in an unsealed `int` slot
						// of the result. Drops the auto-indexed
						// representatives that the unpacked-arg loop
						// inserted (they stand in for "0..N-1 of the
						// unpack value type" and are now subsumed by the
						// unsealed slot).
						$builder = ConstantArrayTypeBuilder::createEmpty();
						$intValues = [];
						foreach ($constantArrays[0]->getKeyTypes() as $i => $keyType) {
							$valueType = $constantArrays[0]->getValueTypes()[$i];
							if ($keyType->isString()->yes()) {
								$builder->setOffsetValueType($keyType, $valueType, $constantArrays[0]->isOptionalKey($i));
								continue;
							}
							$intValues[] = $valueType;
						}

						$unsealedKey = new IntegerType();
						$unsealedValue = count($intValues) > 0 ? TypeCombinator::union(...$intValues) : new MixedType();
						if ($constantArrays[0]->isUnsealed()->yes()) {
							$existing = $constantArrays[0]->getUnsealedTypes();
							if ($existing !== null) {
								$unsealedKey = TypeCombinator::union($unsealedKey, $existing[0]);
								$unsealedValue = TypeCombinator::union($unsealedValue, $existing[1]);
							}
						}
						$builder->makeUnsealed($unsealedKey, $unsealedValue);
						$constantArray = $builder->getArray();
					}
				}

				$newArrayTypes[] = $constantArray;
			}

			return TypeCombinator::union(...$newArrayTypes);
		}

		$setOffsetValueTypes(
			$scope,
			$callArgs,
			static function (?Type $offsetType, Type $valueType, bool $optional) use (&$arrayType): void {
				$isIterableAtLeastOnce = $arrayType->isIterableAtLeastOnce()->yes() || !$optional;
				$arrayType = $arrayType->setOffsetValueType($offsetType, $valueType);
				if ($isIterableAtLeastOnce) {
					return;
				}

				$arrayType = TypeCombinator::union($arrayType, new ConstantArrayType([], []));
			},
		);

		return $arrayType;
	}

	/**
	 * The variable names a compact() call reads, null when they cannot be
	 * enumerated (mirrors CompactFunctionReturnTypeExtension).
	 *
	 * @return list<string>|null
	 */
	private function findCompactVariableNames(FuncCall $funcCall, ArgsResult $argsResult, MutatingScope $scope): ?array
	{
		$names = [];
		foreach ($funcCall->getArgs() as $arg) {
			if ($arg->unpack) {
				return null;
			}
			$argNames = $this->findConstantStringValues($argsResult->requireArgResult($arg->value)->getTypeOnScope($scope, false));
			if ($argNames === null) {
				return null;
			}
			foreach ($argNames as $argName) {
				$names[] = $argName;
			}
		}

		return $names;
	}

	/**
	 * @return list<string>|null
	 */
	private function findConstantStringValues(Type $type): ?array
	{
		$constantStrings = $type->getConstantStrings();
		if (count($constantStrings) > 0) {
			$values = [];
			foreach ($constantStrings as $constantString) {
				$values[] = $constantString->getValue();
			}

			return $values;
		}

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) === 0) {
			return null;
		}
		$values = [];
		foreach ($constantArrays as $constantArray) {
			if ($constantArray->isUnsealed()->yes()) {
				return null;
			}
			foreach ($constantArray->getValueTypes() as $valueType) {
				$valueNames = $this->findConstantStringValues($valueType);
				if ($valueNames === null) {
					return null;
				}
				foreach ($valueNames as $valueName) {
					$values[] = $valueName;
				}
			}
		}

		return $values;
	}

}
