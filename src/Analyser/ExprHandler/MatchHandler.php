<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PerFileAnalysisResettable;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\MatchExpressionArm;
use PHPStan\Node\MatchExpressionArmBody;
use PHPStan\Node\MatchExpressionArmCondition;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use UnhandledMatchError;
use function array_key_exists;
use function array_merge;
use function array_values;
use function count;
use function ksort;
use function spl_object_id;
use function strtolower;
use const SORT_NUMERIC;

/**
 * @implements ExprHandler<Match_>
 */
#[AutowiredService]
final class MatchHandler implements ExprHandler, PerFileAnalysisResettable
{

	/**
	 * Keyed by the match node's spl_object_id() - see
	 * TernaryHandler::$capturedResults for the lifetime/collision reasoning;
	 * the entry pins the node it was captured for.
	 *
	 * @var array<int, array{Match_, list<array{ExpressionResult, MutatingScope, Expr}>}>
	 */
	private array $capturedArmResults = [];

	public function resetFileAnalysisState(): void
	{
		$this->capturedArmResults = [];
	}

	public function __construct(
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private IdenticalNarrowingHelper $identicalNarrowingHelper,
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Match_;
	}

	/**
	 * For each reachable arm of an already-processed match, the arm's body type
	 * together with the scope in which the subject is narrowed to that arm's
	 * condition - the pairs captured during processExpr()'s single walk. Lets
	 * callers reconstruct the relationship between the match result and the
	 * narrowed subject (e.g. to project a later narrowing of the assigned result
	 * back onto the subject) without re-walking the arms. Null when the node was
	 * never processed.
	 *
	 * @return list<array{MutatingScope, Type}>|null
	 */
	public function getCapturedArmScopesAndTypes(Match_ $expr): ?array
	{
		$entry = $this->capturedArmResults[spl_object_id($expr)] ?? null;
		if ($entry === null || $entry[0] !== $expr) {
			return null;
		}

		$pairs = [];
		foreach ($entry[1] as [$armResult, $bodyScope]) {
			$pairs[] = [$bodyScope, $armResult->getType()];
		}

		return $pairs;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$deepContext = $context->enterDeep();
		$condResult = $nodeScopeResolver->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $deepContext);
		// the subject was just processed on this scope; read its result instead of
		// re-walking via Scope::getType().
		$condType = $condResult->getType();
		$condNativeType = $condResult->getNativeType();
		$scope = $condResult->getScope();
		$hasYield = $condResult->hasYield();
		$throwPoints = $condResult->getThrowPoints();
		$impurePoints = $condResult->getImpurePoints();
		$isAlwaysTerminating = $condResult->isAlwaysTerminating();
		$matchScope = $scope->enterMatch($expr, $condType, $condNativeType);
		$armNodes = [];
		$hasDefaultCond = false;
		$hasAlwaysTrueCond = false;
		$arms = $expr->arms;
		$armCondsToSkip = [];
		$armBodyScopes = [];
		// Capture, for each reachable arm, the body's already-computed
		// ExpressionResult together with the scope it was processed on and the
		// body node itself. The typeCallback unions these inside-out instead of
		// re-walking the arms (which getArmScopesAndTypes/the old resolveType
		// did). The set of contributing arms mirrors getArmScopesAndTypes
		// exactly. The body node is kept so the keepVoid projection (the only
		// caller is getKeepVoidType, via a synthetic clone of the match) can be
		// computed for it.
		/** @var list<array{ExpressionResult, MutatingScope, Expr}> $armTypeResults */
		$armTypeResults = [];
		if ($condType->isEnum()->yes()) {
			// enum match analysis would work even without this if branch
			// but would be much slower
			// this avoids using ObjectType::$subtractedType which is slow for huge enums
			// because of repeated union type normalization
			$enumCases = $condType->getEnumCases();
			if (count($enumCases) > 0) {
				$indexedEnumCases = [];
				foreach ($enumCases as $enumCase) {
					$indexedEnumCases[strtolower($enumCase->getClassName())][$enumCase->getEnumCaseName()] = $enumCase;
				}
				$unusedIndexedEnumCases = $indexedEnumCases;
				foreach ($arms as $i => $arm) {
					if ($arm->conds === null) {
						continue;
					}

					// Pre-validate all conditions before processing to avoid
					// partial consumption of enum cases when a later condition
					// causes the arm to be skipped.
					// Use break instead of continue to stop fast-path processing
					// entirely - subsequent arms must also go through the slow
					// path to preserve correct evaluation order.
					foreach ($arm->conds as $cond) {
						if (!$cond instanceof Expr\ClassConstFetch) {
							break 2;
						}
						if (!$cond->class instanceof Name) {
							break 2;
						}
						if (!$cond->name instanceof Identifier) {
							break 2;
						}
						$fetchedClassName = $scope->resolveName($cond->class);
						$loweredFetchedClassName = strtolower($fetchedClassName);
						if (!array_key_exists($loweredFetchedClassName, $indexedEnumCases)) {
							break 2;
						}
						$caseName = $cond->name->toString();
						if (!array_key_exists($caseName, $indexedEnumCases[$loweredFetchedClassName])) {
							break 2;
						}
					}

					$condNodes = [];
					$conditionCases = [];
					$conditionExprs = [];
					foreach ($arm->conds as $j => $cond) {
						// The pre-validation loop above already guaranteed (via break 2)
						// that every reached condition is an enum-case ClassConstFetch.
						if (!$cond->class instanceof Name) {
							throw new ShouldNotHappenException();
						}
						if (!$cond->name instanceof Identifier) {
							throw new ShouldNotHappenException();
						}
						$fetchedClassName = $scope->resolveName($cond->class);
						$loweredFetchedClassName = strtolower($fetchedClassName);
						if (!array_key_exists($loweredFetchedClassName, $indexedEnumCases)) {
							throw new ShouldNotHappenException();
						}

						if (!array_key_exists($loweredFetchedClassName, $unusedIndexedEnumCases)) {
							throw new ShouldNotHappenException();
						}

						$caseName = $cond->name->toString();
						if (!array_key_exists($caseName, $indexedEnumCases[$loweredFetchedClassName])) {
							throw new ShouldNotHappenException();
						}

						$enumCase = $indexedEnumCases[$loweredFetchedClassName][$caseName];
						$conditionCases[] = $enumCase;
						$armConditionScope = $matchScope;
						if (!array_key_exists($caseName, $unusedIndexedEnumCases[$loweredFetchedClassName])) {
							// force "always false"
							$armConditionScope = $armConditionScope->removeTypeFromExpression(
								$expr->cond,
								$enumCase,
							);
						} else {
							$unusedCasesCount = 0;
							foreach ($unusedIndexedEnumCases as $cases) {
								$unusedCasesCount += count($cases);
							}
							if ($unusedCasesCount === 1) {
								$hasAlwaysTrueCond = true;

								// force "always true"
								$armConditionScope = $armConditionScope->addTypeToExpression(
									$expr->cond,
									$enumCase,
								);
							}
						}

						$nodeScopeResolver->processExprNode($stmt, $cond, $armConditionScope, $storage, $nodeCallback, $deepContext);

						$condNodes[] = new MatchExpressionArmCondition(
							$cond,
							$armConditionScope,
							$cond->getStartLine(),
						);
						$conditionExprs[] = $cond;

						unset($unusedIndexedEnumCases[$loweredFetchedClassName][$caseName]);
						$armCondsToSkip[$i][$j] = true;
					}

					$conditionCasesCount = count($conditionCases);
					if ($conditionCasesCount === 0) {
						throw new ShouldNotHappenException();
					} elseif ($conditionCasesCount === 1) {
						$conditionCaseType = $conditionCases[0];
					} else {
						$conditionCaseType = new UnionType($conditionCases);
					}

					$filteringExpr = $this->getFilteringExprForMatchArm($expr, $conditionExprs);
					$condNarrowedScope = $matchScope->addTypeToExpression(
						$expr->cond,
						$conditionCaseType,
					);
					$matchArmBodyScope = $condNarrowedScope->applySpecifiedTypes(
						$nodeScopeResolver->processSyntheticOnDemand($filteringExpr, $condNarrowedScope)->getSpecifiedTypesForScope($condNarrowedScope, TypeSpecifierContext::createTruthy()),
					);
					$matchArmBody = new MatchExpressionArmBody($matchArmBodyScope, $arm->body);
					$armNodes[$i] = new MatchExpressionArm($matchArmBody, $condNodes, $arm->getStartLine());

					$armResult = $nodeScopeResolver->processExprNode(
						$stmt,
						$arm->body,
						$matchArmBodyScope,
						$storage,
						$nodeCallback,
						ExpressionContext::createTopLevel(),
					);
					$armScope = $armResult->getScope();
					if (!$armResult->isAlwaysTerminating()) {
						$armBodyScopes[] = $armScope;
					}
					$hasYield = $hasYield || $armResult->hasYield();
					$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $armResult->getImpurePoints());
					$armTypeResults[] = [$armResult, $matchArmBodyScope, $arm->body];

					unset($arms[$i]);
				}

				$remainingCases = [];
				foreach ($unusedIndexedEnumCases as $cases) {
					foreach ($cases as $case) {
						$remainingCases[] = $case;
					}
				}

				$remainingCasesCount = count($remainingCases);
				if ($remainingCasesCount === 0) {
					$remainingType = new NeverType();
				} elseif ($remainingCasesCount === 1) {
					$remainingType = $remainingCases[0];
				} else {
					$remainingType = new UnionType($remainingCases);
				}

				$matchScope = $matchScope->addTypeToExpression($expr->cond, $remainingType);
			}
		}
		foreach ($arms as $i => $arm) {
			if ($arm->conds === null) {
				$hasDefaultCond = true;
				$defaultArmBodyScope = $matchScope;
				$matchArmBody = new MatchExpressionArmBody($matchScope, $arm->body);
				$armNodes[$i] = new MatchExpressionArm($matchArmBody, [], $arm->getStartLine());
				$armResult = $nodeScopeResolver->processExprNode($stmt, $arm->body, $matchScope, $storage, $nodeCallback, ExpressionContext::createTopLevel());
				$matchScope = $armResult->getScope();
				$hasYield = $hasYield || $armResult->hasYield();
				$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $armResult->getImpurePoints());
				if (!$armResult->isAlwaysTerminating()) {
					$armBodyScopes[] = $matchScope;
				}
				$armTypeResults[] = [$armResult, $defaultArmBodyScope, $arm->body];
				continue;
			}

			if (count($arm->conds) === 0) {
				throw new ShouldNotHappenException();
			}

			$filteringExprs = [];
			$filteringCondData = [];
			$armCondScope = $matchScope;
			$condNodes = [];
			$armCondResultScope = $matchScope;
			$bodyScope = null;
			$condArgResult = $this->identicalNarrowingHelper->captureFirstArgResult($expr->cond, $storage);
			foreach ($arm->conds as $j => $armCond) {
				if (isset($armCondsToSkip[$i][$j])) {
					continue;
				}
				$condNodes[] = new MatchExpressionArmCondition($armCond, $armCondScope, $armCond->getStartLine());
				$armCondResult = $nodeScopeResolver->processExprNode($stmt, $armCond, $armCondScope, $storage, $nodeCallback, $deepContext);
				$hasYield = $hasYield || $armCondResult->hasYield();
				$throwPoints = array_merge($throwPoints, $armCondResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $armCondResult->getImpurePoints());
				$armCondExpr = new BinaryOp\Identical($expr->cond, $armCond);
				$armCondResultScope = $armCondResult->getScope();
				// the `subject === cond` verdict and both narrowing contexts,
				// composed from the subject's THREADED per-arm state (carrying
				// the previous arms' subtractions) and the condition's walk
				// result - no synthetic Identical walk
				$armSubjectType = $armCondResultScope->getStateType($expr->cond);
				$armCondType = $this->treatPhpDocTypesAsCertain
					? $this->richerScopeGetTypeHelper->getIdenticalResult($armCondResultScope, $armCondExpr, $nodeScopeResolver, $armSubjectType, $armCondResult->getType())->type
					: $this->richerScopeGetTypeHelper->getIdenticalResult($armCondResultScope->doNotTreatPhpDocTypesAsCertain(), $armCondExpr, $nodeScopeResolver, $armCondResultScope->doNotTreatPhpDocTypesAsCertain()->getStateType($expr->cond), $armCondResult->getNativeType())->type;
				if ($armCondType->isTrue()->yes()) {
					$hasAlwaysTrueCond = true;
				}
				$armCondArgResult = $this->identicalNarrowingHelper->captureFirstArgResult($armCond, $storage);
				$specifyArmCond = fn (TypeSpecifierContext $specifyContext): SpecifiedTypes => ($this->identicalNarrowingHelper->specifyIdentical(
					$nodeScopeResolver,
					$expr->cond,
					$armCond,
					$condResult,
					$armCondResult,
					$specifyContext,
					$armCondResultScope,
					$condArgResult,
					$armCondArgResult,
					fn (): Type => $this->richerScopeGetTypeHelper->getIdenticalResult($armCondResultScope, $armCondExpr, $nodeScopeResolver, $armCondResultScope->getStateType($expr->cond), $armCondResult->getType())->type,
				) ?? $this->defaultNarrowingHelper->specifyDefaultTypes($armCondExpr, $specifyContext))->setRootExpr($armCondExpr);
				$armCondScope = $armCondResultScope->applySpecifiedTypes($specifyArmCond(TypeSpecifierContext::createFalsey()));
				$armCondTruthyScope = $armCondResultScope->applySpecifiedTypes($specifyArmCond(TypeSpecifierContext::createTruthy()));
				if ($bodyScope === null) {
					$bodyScope = $armCondTruthyScope;
				} else {
					$bodyScope = $bodyScope->mergeWith($armCondTruthyScope);
				}
				$filteringExprs[] = $armCond;
				$filteringCondData[] = [$armCond, $armCondResult];
			}

			if (count($filteringCondData) === 1) {
				// single-condition arm: the filtering expression is the same
				// subject === cond comparison - compose its verdict from the
				// walk results instead of pricing a synthetic node ($bodyScope
				// is always set here, so the multi-cond branch's ??= has no
				// single-cond counterpart)
				if ($bodyScope === null) {
					throw new ShouldNotHappenException();
				}
				[$filteringCond, $filteringCondResult] = $filteringCondData[0];
				$filteringIdentical = new BinaryOp\Identical($expr->cond, $filteringCond);
				$filteringExprType = $this->richerScopeGetTypeHelper->getIdenticalResult($matchScope, $filteringIdentical, $nodeScopeResolver, $matchScope->getStateType($expr->cond), $filteringCondResult->getType())->type;
				// the falsey narrowing stays a synthetic walk: the walk re-prices
				// the subject on the arm-narrowed scope, and that progressive
				// narrowing (each arm sees the subject minus the previous arms'
				// values) is what lets the last arm decide exhaustiveness -
				// composing from the original subject result loses it (bug-6064)
				$filteringFalseyTypes = $nodeScopeResolver->processSyntheticOnDemand($filteringIdentical, $armCondScope)->getSpecifiedTypesForScope($armCondScope, TypeSpecifierContext::createFalsey());
			} else {
				// multi-condition arms compose through in_array so the narrowing
				// stays owned by the in_array type-specifying extension; arms
				// whose conditions were all skipped keep the empty in_array
				// (always false)
				$filteringExpr = $this->getFilteringExprForMatchArm($expr, $filteringExprs, $filteringCondData);
				$filteringExprResult = $nodeScopeResolver->processSyntheticOnDemand($filteringExpr, $matchScope);
				$bodyScope ??= $matchScope->applySpecifiedTypes($filteringExprResult->getSpecifiedTypesForScope($matchScope, TypeSpecifierContext::createTruthy()));
				$filteringExprType = $filteringExprResult->getTypeOnScope($matchScope, false);
				$filteringFalseyTypes = $nodeScopeResolver->processSyntheticOnDemand($filteringExpr, $armCondScope)->getSpecifiedTypesForScope($armCondScope, TypeSpecifierContext::createFalsey());
			}
			$matchArmBody = new MatchExpressionArmBody($bodyScope, $arm->body);
			$armNodes[$i] = new MatchExpressionArm($matchArmBody, $condNodes, $arm->getStartLine());

			$armResult = $nodeScopeResolver->processExprNode(
				$stmt,
				$arm->body,
				$bodyScope,
				$storage,
				$nodeCallback,
				ExpressionContext::createTopLevel(),
			);
			$armScope = $armResult->getScope();
			if (!$armResult->isAlwaysTerminating()) {
				$armBodyScopes[] = $armScope;
			}
			$hasYield = $hasYield || $armResult->hasYield();
			$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $armResult->getImpurePoints());
			// Mirror getArmScopesAndTypes: an arm whose filtering expression is
			// always false is unreachable and does not contribute to the result
			// type.
			if (!$filteringExprType->isFalse()->yes()) {
				$armTypeResults[] = [$armResult, $bodyScope, $arm->body];
			}
			$matchScope = $armCondScope->applySpecifiedTypes($filteringFalseyTypes);
		}

		if (!$hasDefaultCond && !$hasAlwaysTrueCond && $condType->isBoolean()->yes() && $condType->isConstantScalarValue()->yes()) {
			if ($this->isScopeConditionallyImpossible($matchScope)) {
				$hasAlwaysTrueCond = true;
				$matchScope = $matchScope->addTypeToExpression($expr->cond, new NeverType());
			}
		}

		$scopeForMatchNodeCallback = $scope;

		$isExhaustive = $hasDefaultCond || $hasAlwaysTrueCond;
		if (!$isExhaustive) {
			// $matchScope is the subject narrowed by "no arm matched". The arm
			// narrowing is tracked by the scope (getTypeOnScope's authoritative
			// read); only an untracked subject needs reprocessing there.
			$remainingType = $condResult->answersOnScope($matchScope, false)
				? $condResult->getTypeOnScope($matchScope, false)
				: $nodeScopeResolver->processExprOnDemand($expr->cond, $matchScope, new ExpressionResultStorage())->getType();
			if ($remainingType instanceof NeverType) {
				$isExhaustive = true;
			}
		}

		if ($isExhaustive) {
			$armBodyFinalScope = null;
			foreach ($armBodyScopes as $armBodyScope) {
				$armBodyFinalScope = $armBodyScope->mergeWith($armBodyFinalScope);
			}
			$scope = $armBodyFinalScope ?? $scope;
		} else {
			$armBodyFinalScope = null;
			foreach ($armBodyScopes as $armBodyScope) {
				$armBodyFinalScope = $armBodyScope->mergeWith($armBodyFinalScope);
			}
			if ($armBodyFinalScope !== null) {
				$scope = $scope->mergeWith($armBodyFinalScope);
			}
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(UnhandledMatchError::class), $expr, false);
		}

		ksort($armNodes, SORT_NUMERIC);

		$nodeScopeResolver->callNodeCallback($nodeCallback, new MatchExpressionNode($expr->cond, array_values($armNodes), $expr, $matchScope), $scopeForMatchNodeCallback, $storage);

		if ($expr->cond instanceof AlwaysRememberedExpr) {
			$expr->cond = $expr->cond->getExpr();
		}

		$this->capturedArmResults[spl_object_id($expr)] = [$expr, $armTypeResults];

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			// Each arm body was already processed on the scope where the subject
			// is narrowed to that arm's condition - those captured scopes are the
			// evaluation points, so the result type is just the union of the arm
			// body types, no re-walk of the arms needed.
			typeCallback: static function (bool $nativeTypesPromoted) use ($armTypeResults): Type {
				// the union keeps void in the arm bodies (the raw type);
				// ExpressionResult projects void->null for value reads and
				// getKeepVoidType() keeps it, so UsageOfVoidMatchExpressionRule
				// still sees a void arm
				$types = [];
				foreach ($armTypeResults as [$armResult]) {
					$types[] = $armResult->getKeepVoidType($nativeTypesPromoted);
				}

				return TypeCombinator::union(...$types);
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

	/**
	 * @param Expr[] $conditions
	 * @param list<array{Expr, ExpressionResult}> $condData the conditions' walk results, keyed like $conditions
	 */
	private function getFilteringExprForMatchArm(Match_ $expr, array $conditions, array $condData = []): BinaryOp\Identical|FuncCall
	{
		if (count($conditions) === 1) {
			return new BinaryOp\Identical($expr->cond, $conditions[0]);
		}

		// The haystack carries the conditions' walked types, not their nodes: a
		// condition node is narrowed to never on the arm's own falsey scope
		// ($subject === $cond specifies both sides), so re-pricing it there would
		// collapse every condition after the first and stop the arm subtracting.
		$items = [];
		foreach ($conditions as $i => $filteringExpr) {
			$condResult = $condData[$i][1] ?? null;
			$items[] = new Node\ArrayItem(
				$condResult !== null ? new TypeExpr($condResult->getType()) : $filteringExpr,
			);
		}

		return new FuncCall(
			new Name\FullyQualified('in_array'),
			[
				new Arg($expr->cond),
				new Arg(new Array_($items)),
				new Arg(new ConstFetch(new Name\FullyQualified('true'))),
			],
		);
	}

	private function isScopeConditionallyImpossible(MutatingScope $scope): bool
	{
		$boolVars = [];
		foreach ($scope->getDefinedVariables() as $varName) {
			$varType = $scope->getVariableType($varName);
			if (!$varType->isBoolean()->yes() || $varType->isConstantScalarValue()->yes()) {
				continue;
			}

			$boolVars[] = $varName;
		}

		if ($boolVars === []) {
			return false;
		}

		// Check if any boolean variable's both truth values lead to contradictions
		foreach ($boolVars as $varName) {
			$varExpr = new Variable($varName);
			// a walked Variable's specify callback is exactly the default
			// narrowing - no need to price the synthetic node on demand

			$truthyScope = $scope->applySpecifiedTypes($this->defaultNarrowingHelper->specifyDefaultTypes($varExpr, TypeSpecifierContext::createTruthy()));
			$truthyContradiction = $this->scopeHasNeverVariable($truthyScope, $boolVars);
			if (!$truthyContradiction) {
				continue;
			}

			$falseyScope = $scope->applySpecifiedTypes($this->defaultNarrowingHelper->specifyDefaultTypes($varExpr, TypeSpecifierContext::createFalsey()));
			$falseyContradiction = $this->scopeHasNeverVariable($falseyScope, $boolVars);
			if ($falseyContradiction) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string[] $varNames
	 */
	private function scopeHasNeverVariable(MutatingScope $scope, array $varNames): bool
	{
		foreach ($varNames as $varName) {
			$type = $scope->getVariableType($varName);
			if ($type instanceof NeverType) {
				return true;
			}
		}

		return false;
	}

}
