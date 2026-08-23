<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use IteratorAggregate;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\RecordingNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VarAnnotationProcessor;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\Node\InForeachNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Traversable;
use function array_fill_keys;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @implements StmtHandler<Foreach_>
 */
#[AutowiredService]
final class ForeachHandler implements StmtHandler
{

	private const FOREACH_UNROLL_LIMIT = 16;
	private const FOREACH_UNROLL_NESTED_LIMIT = 8;

	public function __construct(
		private VarAnnotationProcessor $varAnnotationProcessor,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Foreach_;
	}

	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$entryScope = $scope;
		if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
			$scope = $this->varAnnotationProcessor->processVarAnnotation($scope, [$stmt->expr->name], $stmt);
		}
		$condResult = $nodeScopeResolver->processExprNode($stmt, $stmt->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);
		$throwPoints = $condResult->getThrowPoints();
		$impurePoints = $condResult->getImpurePoints();
		$scope = $condResult->getScope();
		$arrayComparisonExpr = new BinaryOp\NotIdentical(
			$stmt->expr,
			new Array_([]),
		);
		$nodeScopeResolver->callNodeCallback($nodeCallback, new InForeachNode($stmt), $scope, $storage);
		$originalScope = $scope;
		$bodyScope = $scope;

		$foreachIterateeType = $originalScope->getType($stmt->expr);
		$foreachNativeIterateeType = $originalScope->getNativeType($stmt->expr);

		if ($stmt->keyVar instanceof Variable) {
			$keyTypeExpr = new NativeTypeExpr(
				$originalScope->getIterableKeyType($foreachIterateeType),
				$originalScope->getIterableKeyType($foreachNativeIterateeType),
			);
			$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($stmt->keyVar, $keyTypeExpr), $originalScope, $storage);
		}

		if ($stmt->valueVar instanceof Variable) {
			$valueTypeExpr = new NativeTypeExpr(
				$originalScope->getIterableValueType($foreachIterateeType),
				$originalScope->getIterableValueType($foreachNativeIterateeType),
			);
			$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($stmt->valueVar, $valueTypeExpr), $originalScope, $storage);
		} elseif ($stmt->valueVar instanceof List_) {
			$virtualAssign = new Assign($stmt->valueVar, new NativeTypeExpr(
				$originalScope->getIterableValueType($foreachIterateeType),
				$originalScope->getIterableValueType($foreachNativeIterateeType),
			));
			$virtualAssign->setAttributes($stmt->valueVar->getAttributes());
			$nodeScopeResolver->callNodeCallback($nodeCallback, $virtualAssign, $scope, $storage);
		}

		$iterateeScope = $nodeScopeResolver->shouldPolluteScopeWithAlwaysIterableForeach() ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope;

		$originalStorage = $storage;
		$replayBodyRecording = null;
		$replayPassStorage = null;
		$replayPassResult = null;
		$replayEntryScope = null;
		$unrolledEndScope = null;
		$unrolledTotalKeys = null;
		if ($context->isTopLevel()) {
			$storage = $originalStorage->duplicate();

			$originalScope = $iterateeScope;
			$foreachIterateeType = $originalScope->getType($stmt->expr);
			$foreachNativeIterateeType = $originalScope->getNativeType($stmt->expr);
			$unrolledResult = $this->tryProcessUnrolledConstantArrayForeach($nodeScopeResolver, $stmt, $originalScope, $originalStorage, $context, $foreachIterateeType, $foreachNativeIterateeType);
			if ($unrolledResult !== null) {
				$bodyScope = $unrolledResult['bodyScope'];
				$unrolledEndScope = $unrolledResult['endScope'];
				$unrolledTotalKeys = $unrolledResult['totalKeys'];
			} else {
				$bodyScope = $this->enterForeach($nodeScopeResolver, $originalScope, $storage, $originalScope, $stmt, $foreachIterateeType, $foreachNativeIterateeType, $nodeCallback);
				$count = 0;
				$prevEntryScope = null;
				$bodyIsReplayable = $nodeScopeResolver->isReplayableConvergenceBody($stmt, $stmt->stmts);
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($iterateeScope);
					if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
						// walking is deterministic in the entry scope - an unchanged entry
						// reproduces the previous pass's exit, so the verification walk is skipped
						$bodyScope = $prevScope;
						break;
					}
					$prevEntryScope = $bodyScope;
					$storage = $originalStorage->duplicate();
					$bodyScope = $this->enterForeach($nodeScopeResolver, $bodyScope, $storage, $originalScope, $stmt, $foreachIterateeType, $foreachNativeIterateeType, $nodeCallback);
					$bodyRecording = $bodyIsReplayable ? new RecordingNodeCallback() : new NoopNodeCallback();
					$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $bodyRecording, $context->enterDeep())->filterOutLoopExitPoints();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}
					// the candidate to replace the final walk when this pass's
					// entry turns out to be the fixpoint
					if ($bodyRecording instanceof RecordingNodeCallback) {
						$replayBodyRecording = $bodyRecording;
						$replayPassStorage = $storage;
						$replayPassResult = $bodyScopeResult;
						$replayEntryScope = $prevEntryScope;
					}
					if ($bodyScope->equals($prevScope)) {
						break;
					}

					if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
						$bodyScope = $prevScope->generalizeWith($bodyScope);
					}
					$count++;
				} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);
			}
		}

		$bodyScope = $bodyScope->mergeWith($iterateeScope);
		$finalEntryScope = $bodyScope;
		$storage = $originalStorage;
		$bodyScope = $this->enterForeach($nodeScopeResolver, $bodyScope, $storage, $originalScope, $stmt, $foreachIterateeType, $foreachNativeIterateeType, $nodeCallback);
		if (
			$replayBodyRecording !== null && $replayPassStorage !== null
			&& $replayPassResult !== null && $replayEntryScope !== null
			&& $unrolledTotalKeys === null && $finalEntryScope->equals($replayEntryScope)
		) {
			// the final walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's results
			// and replay its emissions through the real callback instead
			$originalStorage->mergeResults($replayPassStorage);
			$nodeScopeResolver->replayRecording($replayBodyRecording, $nodeCallback, $originalStorage);
			$finalScopeResult = $replayPassResult;
		} else {
			$finalPassContext = $unrolledTotalKeys !== null ? $context->enterUnrolledForeach($unrolledTotalKeys) : $context;
			$finalScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $finalPassContext)->filterOutLoopExitPoints();
		}
		$finalScope = $finalScopeResult->getScope();
		$scopesWithIterableValueType = [];

		$keyVarExpr = null;
		$originalKeyVarExpr = null;
		if ($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)) {
			$keyVarExpr = $stmt->keyVar;
			$originalKeyVarExpr = new OriginalForeachKeyExpr($stmt->keyVar->name);
		}
		$originalValueExpr = null;
		if ($stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name)) {
			$originalValueExpr = new OriginalForeachValueExpr($stmt->valueVar->name);
		}

		// With a key variable, each iteration is tracked through the original key
		// expression and the narrowed element is projected onto the array dim fetch.
		// Without one (`foreach ($a as $v)`) we instead track the original value
		// expression and rewrite the array value type directly from the value var.
		$trackingExpr = $originalKeyVarExpr ?? $originalValueExpr;

		$continueExitPointHasUnoriginalKeyType = false;
		if ($trackingExpr !== null) {
			if ($finalScope->hasExpressionType($trackingExpr)->yes()) {
				$scopesWithIterableValueType[] = $finalScope;
			} else {
				$continueExitPointHasUnoriginalKeyType = true;
			}
		}

		foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
			$continueScope = $continueExitPoint->getScope();
			$finalScope = $continueScope->mergeWith($finalScope);
			if ($trackingExpr === null || !$continueScope->hasExpressionType($trackingExpr)->yes()) {
				$continueExitPointHasUnoriginalKeyType = true;
				continue;
			}
			$scopesWithIterableValueType[] = $continueScope;
		}
		$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
		foreach ($breakExitPoints as $breakExitPoint) {
			$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
		}

		if ($unrolledEndScope !== null) {
			$finalScope = $unrolledEndScope;
		}

		// $scope is the post-loop scope; the body may have modified the iteratee
		// (e.g. $arr[] = ...). A tracked iteratee reads the modified type off the
		// scope (getTypeOnScope's authoritative read); only an untracked one whose
		// inputs the body changed needs reprocessing there to observe it.
		$exprType = $scope->getType($stmt->expr);
		$hasExpr = $scope->hasExpressionType($stmt->expr);
		if (
			count($breakExitPoints) === 0
			&& count($scopesWithIterableValueType) > 0
			&& !$continueExitPointHasUnoriginalKeyType
			&& ($keyVarExpr !== null || $originalValueExpr !== null)
			&& (!$hasExpr->no() || !$stmt->expr instanceof Variable)
			&& $exprType->isArray()->yes()
			&& $exprType->isConstantArray()->no()
		) {
			$arrayDimFetchLoopTypes = [];
			$arrayDimFetchLoopNativeTypes = [];
			$keyLoopTypes = [];
			$keyLoopNativeTypes = [];
			foreach ($scopesWithIterableValueType as $scopeWithIterableValueType) {
				if ($keyVarExpr !== null) {
					$arrayExprDimFetch = new ArrayDimFetch($stmt->expr, $keyVarExpr);
					// enterForeach tracks this exact dim fetch - the tracked-holder
					// fast path answers without pricing the synthetic node
					$dimFetchType = $scopeWithIterableValueType->getType($arrayExprDimFetch);
					$dimFetchNativeType = $scopeWithIterableValueType->getNativeType($arrayExprDimFetch);
					// Condition-based narrowings like `is_string($type)` apply to the value
					// variable but not automatically to the array dim fetch, even though the
					// two describe the same element for a given iteration. If the value var
					// hasn't been reassigned (OriginalForeachValueExpr still tracked) we use
					// the narrowed value-var type in place of the broader dim fetch type so
					// the loop's final array rewrite below picks up the sharper element type.
					if ($originalValueExpr !== null && $scopeWithIterableValueType->hasExpressionType($originalValueExpr)->yes()) {
						// read the loop value variable's narrowed type directly by name -
						// it is an assigned (not processExprNode-processed) variable, so
						// getVariableType() consumes its tracked type without pricing the
						// unprocessed node on demand. ($originalValueExpr !== null implies
						// the value var is a string-named Variable.)
						$valueVarType = $scopeWithIterableValueType->getVariableType($stmt->valueVar->name);
						if ($dimFetchType->isSuperTypeOf($valueVarType)->yes()) {
							$dimFetchType = $valueVarType;
						}
						$valueVarNativeType = $scopeWithIterableValueType->getNativeType($stmt->valueVar);
						if ($dimFetchNativeType->isSuperTypeOf($valueVarNativeType)->yes()) {
							$dimFetchNativeType = $valueVarNativeType;
						}
					}
					$keyLoopTypes[] = $scopeWithIterableValueType->getType($keyVarExpr);
					$keyLoopNativeTypes[] = $scopeWithIterableValueType->getNativeType($keyVarExpr);
				} else {
					// No key variable: the narrowed value var is the array element type
					// directly. Read it by name (assigned, not processExprNode-processed);
					// no key var implies $originalValueExpr !== null, so the value var is
					// a string-named Variable.
					$dimFetchType = $scopeWithIterableValueType->getVariableType($stmt->valueVar->name);
					$dimFetchNativeType = $scopeWithIterableValueType->getNativeType($stmt->valueVar);
				}
				$arrayDimFetchLoopTypes[] = $dimFetchType;
				$arrayDimFetchLoopNativeTypes[] = $dimFetchNativeType;
			}

			$arrayDimFetchLoopType = TypeCombinator::union(...$arrayDimFetchLoopTypes);
			$arrayDimFetchLoopNativeType = TypeCombinator::union(...$arrayDimFetchLoopNativeTypes);

			$valueTypeChanged = !$arrayDimFetchLoopType->equals($exprType->getIterableValueType());
			$keyTypeChanged = false;
			$keyLoopType = $exprType->getIterableKeyType();
			$keyLoopNativeType = $scope->getNativeType($stmt->expr)->getIterableKeyType();
			if ($keyVarExpr !== null) {
				$keyLoopType = TypeCombinator::union(...$keyLoopTypes);
				$keyLoopNativeType = TypeCombinator::union(...$keyLoopNativeTypes);
				$keyTypeChanged = !$keyLoopType->equals($exprType->getIterableKeyType());
			}

			if ($valueTypeChanged || $keyTypeChanged) {
				$newExprType = $exprType;
				if ($valueTypeChanged) {
					$newExprType = $newExprType->mapValueType(static fn (Type $type): Type => $arrayDimFetchLoopType);
				}
				if ($keyTypeChanged) {
					$newExprType = $newExprType->mapKeyType(static fn (Type $type): Type => $keyLoopType);
				}

				$nativeExprType = $scope->getNativeType($stmt->expr);
				$newExprNativeType = $nativeExprType;
				if ($valueTypeChanged) {
					$newExprNativeType = $newExprNativeType->mapValueType(static fn (Type $type): Type => $arrayDimFetchLoopNativeType);
				}
				if ($keyTypeChanged) {
					$newExprNativeType = $newExprNativeType->mapKeyType(static fn (Type $type): Type => $keyLoopNativeType);
				}

				if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
					$finalScope = $finalScope->assignVariable(
						$stmt->expr->name,
						$newExprType,
						$newExprNativeType,
						$hasExpr,
					);
				} else {
					$finalScope = $finalScope->assignExpression(
						$stmt->expr,
						$newExprType,
						$newExprNativeType,
					);
				}
			}
		}

		$isIterableAtLeastOnce = $exprType->isIterableAtLeastOnce();
		if ($isIterableAtLeastOnce->maybe() || $exprType->isIterable()->no()) {
			$finalScope = $finalScope->mergeWith($scope->filterByTruthyValue(new BooleanOr(
				new BinaryOp\Identical(
					$stmt->expr,
					new Array_([]),
				),
				new FuncCall(new Name\FullyQualified('is_object'), [
					new Arg($stmt->expr),
				]),
			)));
		} elseif ($isIterableAtLeastOnce->no() || $finalScopeResult->isAlwaysTerminating()) {
			$finalScope = $scope;
		} elseif (!$nodeScopeResolver->shouldPolluteScopeWithAlwaysIterableForeach()) {
			$finalScope = $scope->processAlwaysIterableForeachScopeWithoutPollute($finalScope);
			// get types from finalScope, but don't create new variables
		}

		if (!$isIterableAtLeastOnce->no()) {
			$throwPoints = array_merge($throwPoints, $finalScopeResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $finalScopeResult->getImpurePoints());
		}
		$traversableThrowPoint = $this->getTraversableForeachThrowPoint($scope, $stmt->expr);
		if ($traversableThrowPoint !== null) {
			$throwPoints[] = $traversableThrowPoint;
		}
		if ($context->isTopLevel() && $stmt->byRef) {
			$finalScope = $finalScope->assignExpression(new ForeachValueByRefExpr($stmt->valueVar), new MixedType(), new MixedType());
		}

		return new InternalStatementResult(
			$finalScope,
			hasYield: $finalScopeResult->hasYield() || $condResult->hasYield(),
			isAlwaysTerminating: $isIterableAtLeastOnce->yes() && $finalScopeResult->isAlwaysTerminating(),
			exitPoints: $finalScopeResult->getExitPointsForOuterLoop(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function enterForeach(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, MutatingScope $originalScope, Foreach_ $stmt, Type $iterateeType, Type $nativeIterateeType, callable $nodeCallback): MutatingScope
	{
		if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
			$scope = $this->varAnnotationProcessor->processVarAnnotation($scope, [$stmt->expr->name], $stmt);
		}

		if (
			($stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name))
			&& ($stmt->keyVar === null || ($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)))
		) {
			$keyVarName = $stmt->keyVar instanceof Variable ? $stmt->keyVar->name : null;
			$scope = $scope->enterForeach(
				$originalScope,
				$stmt->expr,
				$iterateeType,
				$nativeIterateeType,
				$stmt->valueVar->name,
				$keyVarName,
				$stmt->byRef,
			);
			$vars = [$stmt->valueVar->name];
			if ($keyVarName !== null) {
				$vars[] = $keyVarName;
			}
		} else {
			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$stmt->valueVar,
				new NativeTypeExpr(
					$originalScope->getIterableValueType($iterateeType),
					$originalScope->getIterableValueType($nativeIterateeType),
				),
				$nodeCallback,
			)->getScope();
			$vars = $nodeScopeResolver->getAssignedVariables($stmt->valueVar);
			if (
				$stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)
			) {
				$scope = $scope->enterForeachKey($originalScope, $stmt->expr, $iterateeType, $nativeIterateeType, $stmt->keyVar->name);
				$vars[] = $stmt->keyVar->name;
			} elseif ($stmt->keyVar !== null) {
				$scope = $nodeScopeResolver->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$stmt->keyVar,
					new NativeTypeExpr(
						$originalScope->getIterableKeyType($iterateeType),
						$originalScope->getIterableKeyType($nativeIterateeType),
					),
					$nodeCallback,
				)->getScope();
				$vars = array_merge($vars, $nodeScopeResolver->getAssignedVariables($stmt->keyVar));
			}

			if ($stmt->valueVar instanceof List_) {
				$scope = $this->addDestructureTaggedUnionConditionalHolders(
					$nodeScopeResolver,
					$scope,
					$originalScope->getIterableValueType($iterateeType),
					$stmt->valueVar,
				);
			}
		}

		$constantArrays = $iterateeType->getConstantArrays();
		if (
			$stmt->getDocComment() === null
			&& $iterateeType->isConstantArray()->yes()
			&& count($constantArrays) === 1
			&& $stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name)
			&& $stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)
		) {
			$valueConditionalHolders = [];
			$arrayDimFetchConditionalHolders = [];
			foreach ($constantArrays[0]->getKeyTypes() as $i => $keyType) {
				$valueType = $constantArrays[0]->getValueTypes()[$i];
				$keyExpressionTypeHolder = ExpressionTypeHolder::createYes(new Variable($stmt->keyVar->name), $keyType);

				$holder = new ConditionalExpressionHolder([
					'$' . $stmt->keyVar->name => $keyExpressionTypeHolder,
				], ExpressionTypeHolder::createYes($stmt->valueVar, $valueType));
				$valueConditionalHolders[$holder->getKey()] = $holder;
				$arrayDimFetchHolder = new ConditionalExpressionHolder([
					'$' . $stmt->keyVar->name => $keyExpressionTypeHolder,
				], ExpressionTypeHolder::createYes(new ArrayDimFetch($stmt->expr, $stmt->keyVar), $valueType));
				$arrayDimFetchConditionalHolders[$arrayDimFetchHolder->getKey()] = $arrayDimFetchHolder;
			}

			$scope = $scope->addConditionalExpressions(
				'$' . $stmt->valueVar->name,
				$valueConditionalHolders,
			);
			if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
				$scope = $scope->addConditionalExpressions(
					sprintf('$%s[$%s]', $stmt->expr->name, $stmt->keyVar->name),
					$arrayDimFetchConditionalHolders,
				);
			}
		}

		if (
			$stmt->expr instanceof FuncCall
			&& $stmt->expr->name instanceof Name
			&& !$stmt->expr->isFirstClassCallable()
			&& $stmt->expr->name->toLowerString() === 'array_keys'
			&& $stmt->valueVar instanceof Variable
		) {
			$args = $stmt->expr->getArgs();
			if (count($args) >= 1) {
				$arrayArg = $args[0]->value;
				$scope = $scope->assignExpression(
					new ArrayDimFetch($arrayArg, $stmt->valueVar),
					$scope->getType($arrayArg)->getIterableValueType(),
					$scope->getNativeType($arrayArg)->getIterableValueType(),
				);
			}
		}

		return $this->varAnnotationProcessor->processVarAnnotation($scope, $vars, $stmt);
	}

	/**
	 * @return array{bodyScope: MutatingScope, endScope: MutatingScope, totalKeys: int}|null
	 */
	private function tryProcessUnrolledConstantArrayForeach(
		NodeScopeResolver $nodeScopeResolver,
		Foreach_ $stmt,
		MutatingScope $originalScope,
		ExpressionResultStorage $originalStorage,
		StatementContext $context,
		Type $iterateeType,
		Type $nativeIterateeType,
	): ?array
	{
		if ($stmt->byRef) {
			return null;
		}
		if (!($stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name))) {
			return null;
		}
		if ($stmt->keyVar !== null && !($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name))) {
			return null;
		}

		if (!$iterateeType->isConstantArray()->yes()) {
			return null;
		}
		$constantArrays = $iterateeType->getConstantArrays();
		if (count($constantArrays) === 0) {
			return null;
		}

		$totalKeys = 0;
		$hasUnsealed = false;
		foreach ($constantArrays as $constantArray) {
			$totalKeys += count($constantArray->getKeyTypes());
			if (!$constantArray->isUnsealed()->yes()) {
				continue;
			}
			$hasUnsealed = true;
		}
		if ($totalKeys === 0 || $totalKeys > self::FOREACH_UNROLL_LIMIT) {
			return null;
		}
		$foreachUnrollFactor = $context->getForeachUnrollFactor();
		if ($foreachUnrollFactor > 1 && $foreachUnrollFactor * $totalKeys > self::FOREACH_UNROLL_NESTED_LIMIT) {
			return null;
		}

		$nativeConstantArrays = $nativeIterateeType->getConstantArrays();
		$matchedNativeArrays = count($nativeConstantArrays) === count($constantArrays) ? $nativeConstantArrays : null;

		$valueVarName = $stmt->valueVar->name;
		$keyVarName = $stmt->keyVar instanceof Variable ? $stmt->keyVar->name : null;

		$allBodyScopes = [];
		$allChainScopes = [];
		$allBreakScopes = [];

		$bodyContext = $context->enterUnrolledForeach($totalKeys);

		foreach ($constantArrays as $arrayIndex => $constantArray) {
			$keyTypes = $constantArray->getKeyTypes();
			$valueTypes = $constantArray->getValueTypes();
			if (count($keyTypes) === 0) {
				continue;
			}

			$nativeConstantArray = $matchedNativeArrays !== null ? $matchedNativeArrays[$arrayIndex] : null;
			$optionalKeys = array_fill_keys($constantArray->getOptionalKeys(), true);

			$chainScope = $originalScope;
			$entryScopes = [];

			foreach ($keyTypes as $i => $keyType) {
				$valueType = $valueTypes[$i];
				$isOptional = isset($optionalKeys[$i]);

				$nativeKeyType = $nativeConstantArray !== null && isset($nativeConstantArray->getKeyTypes()[$i])
					? $nativeConstantArray->getKeyTypes()[$i]
					: $keyType;
				$nativeValueType = $nativeConstantArray !== null && isset($nativeConstantArray->getValueTypes()[$i])
					? $nativeConstantArray->getValueTypes()[$i]
					: $valueType;

				$iterScope = $chainScope->assignVariable(
					$valueVarName,
					$valueType,
					$nativeValueType,
					TrinaryLogic::createYes(),
				);
				$iterScope = $iterScope->assignExpression(
					new OriginalForeachValueExpr($valueVarName),
					$valueType,
					$nativeValueType,
				);
				if ($keyVarName !== null) {
					$iterScope = $iterScope->assignVariable(
						$keyVarName,
						$keyType,
						$nativeKeyType,
						TrinaryLogic::createYes(),
					);
					$iterScope = $iterScope->assignExpression(
						new OriginalForeachKeyExpr($keyVarName),
						$keyType,
						$nativeKeyType,
					);
					$iterScope = $iterScope->assignExpression(
						new ArrayDimFetch($stmt->expr, $stmt->keyVar),
						$valueType,
						$nativeValueType,
					);
				}

				$entryScopes[] = $iterScope;

				$iterStorage = $originalStorage->duplicate();
				$bodyResult = $nodeScopeResolver->processStmtNodesInternal(
					$stmt,
					$stmt->stmts,
					$iterScope,
					$iterStorage,
					new NoopNodeCallback(),
					$bodyContext,
				)->filterOutLoopExitPoints();

				$iterEndScope = $bodyResult->getScope();
				foreach ($bodyResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$iterEndScope = $iterEndScope->mergeWith($continueExitPoint->getScope());
				}
				foreach ($bodyResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
					$allBreakScopes[] = $breakExitPoint->getScope();
				}

				if ($isOptional) {
					$chainScope = $iterEndScope->mergeWith($chainScope);
				} else {
					$chainScope = $iterEndScope;
				}
			}

			$arrayBodyScope = $entryScopes[0];
			for ($i = 1, $c = count($entryScopes); $i < $c; $i++) {
				$arrayBodyScope = $arrayBodyScope->mergeWith($entryScopes[$i]);
			}
			if (count($entryScopes) === 1) {
				$arrayBodyScope = $arrayBodyScope->mergeWith($chainScope);
			}

			$allBodyScopes[] = $arrayBodyScope;
			$allChainScopes[] = $chainScope;
		}

		if ($allBodyScopes === []) {
			return null;
		}

		$bodyScope = $allBodyScopes[0];
		for ($i = 1, $c = count($allBodyScopes); $i < $c; $i++) {
			$bodyScope = $bodyScope->mergeWith($allBodyScopes[$i]);
		}

		$endScope = $allChainScopes[0];
		for ($i = 1, $c = count($allChainScopes); $i < $c; $i++) {
			$endScope = $endScope->mergeWith($allChainScopes[$i]);
		}

		foreach ($allBreakScopes as $breakScope) {
			$endScope = $endScope->mergeWith($breakScope);
		}

		// Unsealed shapes describe zero-or-more additional entries beyond the
		// explicit keys. Run the scope-generalizing loop on top of the
		// unrolled explicit iterations so body-scope variables (e.g. counters)
		// account for the extra iterations while keeping the lower bound
		// established by the non-optional explicit keys.
		if ($hasUnsealed) {
			$loopScope = $endScope;
			$count = 0;
			do {
				$prevLoopScope = $loopScope;
				$iterStorage = $originalStorage->duplicate();
				$iterBodyScope = $loopScope->mergeWith($endScope);
				$iterBodyScope = $this->enterForeach($nodeScopeResolver, $iterBodyScope, $iterStorage, $originalScope, $stmt, $iterateeType, $nativeIterateeType, new NoopNodeCallback());
				$iterBodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $iterBodyScope, $iterStorage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
				$loopScope = $iterBodyScopeResult->getScope();
				foreach ($iterBodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$loopScope = $loopScope->mergeWith($continueExitPoint->getScope());
				}
				foreach ($iterBodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
					$endScope = $endScope->mergeWith($breakExitPoint->getScope());
				}
				$bodyScope = $bodyScope->mergeWith($loopScope);
				if ($loopScope->equals($prevLoopScope)) {
					break;
				}
				if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
					$loopScope = $prevLoopScope->generalizeWith($loopScope);
				}
				$count++;
			} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);

			$endScope = $endScope->mergeWith($loopScope);
		}

		return ['bodyScope' => $bodyScope, 'endScope' => $endScope, 'totalKeys' => $totalKeys];
	}

	private function getTraversableForeachThrowPoint(MutatingScope $scope, Expr $iteratee): ?InternalThrowPoint
	{
		$exprType = $scope->getType($iteratee);
		$traversableType = new ObjectType(Traversable::class);

		if ($traversableType->isSuperTypeOf($exprType)->no()) {
			return null;
		}

		$traversablePart = TypeCombinator::intersect($exprType, $traversableType);
		$iteratorAggregateType = new ObjectType(IteratorAggregate::class);

		if ($iteratorAggregateType->isSuperTypeOf($traversablePart)->yes()
			&& $traversablePart->hasMethod('getIterator')->yes()) {
			$method = $traversablePart->getMethod('getIterator', $scope);
			$throwType = $method->getThrowType();
			if ($throwType !== null) {
				if ($throwType->isVoid()->yes()) {
					return null;
				}
				return InternalThrowPoint::createExplicit($scope, $throwType, $iteratee, true);
			}

			if (!$this->implicitThrows) {
				return null;
			}
		}

		return InternalThrowPoint::createImplicit($scope, $iteratee);
	}

	/**
	 * When destructuring an iterable whose value type is a tagged union of
	 * constant arrays — e.g. `array<array{null, int}|array{int, null}>` — the
	 * variants describe a relationship between the destructured variables that
	 * a per-variable narrowing would normally lose: knowing `$x === null` should
	 * imply `$y === int`, but `foreach ($a as [$x, $y])` assigns `$x` and `$y`
	 * independently, so each ends up as the union (`int|null`) and the link is
	 * dropped.
	 *
	 * Recover the link by storing conditional-expression holders on each
	 * destructured variable: for every variant, "when this variable matches the
	 * variant's value at its position, the other variables match the variant's
	 * values at their positions". A later `if ($x === null)` then fires the
	 * matching holder and narrows `$y` accordingly.
	 *
	 * Only handles flat positional / keyed destructure patterns (List_) where
	 * each item's target is a plain Variable; nested destructure is left for
	 * the regular per-variable type tracking.
	 */
	private function addDestructureTaggedUnionConditionalHolders(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $scope,
		Type $iterableValueType,
		List_ $list,
	): MutatingScope
	{
		$constantArrays = $iterableValueType->getConstantArrays();
		if (count($constantArrays) < 2) {
			return $scope;
		}

		// Collect each list item's array-key value and target variable.
		$items = [];
		foreach ($list->items as $position => $item) {
			if ($item === null) {
				continue;
			}
			if (!$item->value instanceof Variable || !is_string($item->value->name)) {
				return $scope;
			}
			if ($item->key === null) {
				$keyValue = $position;
			} elseif ($item->key instanceof Node\Scalar\String_) {
				$keyValue = $item->key->value;
			} elseif ($item->key instanceof Node\Scalar\Int_) {
				$keyValue = $item->key->value;
			} else {
				return $scope;
			}
			$items[] = ['key' => $keyValue, 'name' => $item->value->name];
		}

		if (count($items) < 2) {
			return $scope;
		}

		// For every variant, every item must have a matching key with a single
		// value type at it; otherwise the variants don't all describe the same
		// destructure shape and we can't form a sound holder set.
		$variantValuesByItem = [];
		foreach ($items as $itemIdx => $itemInfo) {
			$variantValuesByItem[$itemIdx] = [];
			foreach ($constantArrays as $variantIdx => $variant) {
				$keyType = is_int($itemInfo['key']) ? new ConstantIntegerType($itemInfo['key']) : new ConstantStringType($itemInfo['key']);
				if (!$variant->hasOffsetValueType($keyType)->yes()) {
					return $scope;
				}
				$variantValuesByItem[$itemIdx][$variantIdx] = $variant->getOffsetValueType($keyType);
			}
		}

		// For each item × variant, build a holder: "when item is variant's value
		// at this position, the *other* items are the variant's values at their
		// positions". Skip the variant if the condition value is too wide to be
		// a useful discriminator (i.e. equal to the union of all the variant
		// values at this position — narrowing it back wouldn't pick a variant).
		foreach ($items as $itemIdx => $itemInfo) {
			$exprString = '$' . $itemInfo['name'];
			$variantConditionTypes = $variantValuesByItem[$itemIdx];
			$itemUnionType = TypeCombinator::union(...array_values($variantConditionTypes));
			$holders = [];
			foreach (array_keys($constantArrays) as $variantIdx) {
				$conditionType = $variantConditionTypes[$variantIdx];
				if ($conditionType->equals($itemUnionType)) {
					continue;
				}
				$conditions = [
					$exprString => ExpressionTypeHolder::createYes(new Variable($itemInfo['name']), $conditionType),
				];
				foreach ($items as $otherIdx => $otherInfo) {
					if ($otherIdx === $itemIdx) {
						continue;
					}
					$otherType = $variantValuesByItem[$otherIdx][$variantIdx];
					$holder = new ConditionalExpressionHolder(
						$conditions,
						ExpressionTypeHolder::createYes(new Variable($otherInfo['name']), $otherType),
					);
					$holders['$' . $otherInfo['name']][$holder->getKey()] = $holder;
				}
			}

			foreach ($holders as $targetExprString => $targetHolders) {
				$scope = $scope->addConditionalExpressions($targetExprString, $targetHolders);
			}
		}

		return $scope;
	}

}
