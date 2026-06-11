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
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
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
use function strtolower;
use const SORT_NUMERIC;

/**
 * @implements ExprHandler<Match_>
 */
#[AutowiredService]
final class MatchHandler implements ExprHandler
{

	public function __construct(
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Match_;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$types = [];
		foreach ($this->getArmScopesAndTypes($scope, $expr) as [$armScope, $armType]) {
			$types[] = $armType;
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * For each reachable match arm, returns the arm's body type together with the
	 * scope in which the match subject is narrowed to that arm's condition. This
	 * lets callers reconstruct the relationship between the match result and the
	 * narrowed subject (e.g. to project a later narrowing of the assigned result
	 * back onto the subject).
	 *
	 * @return list<array{MutatingScope, Type}>
	 */
	public function getArmScopesAndTypes(MutatingScope $scope, Match_ $expr): array
	{
		$cond = $expr->cond;
		$condType = $scope->getType($cond);
		$armScopesAndTypes = [];

		$matchScope = $scope;
		$arms = $expr->arms;
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

					$conditionCases = [];
					foreach ($arm->conds as $armCond) {
						if (!$armCond instanceof Expr\ClassConstFetch) {
							continue 2;
						}
						if (!$armCond->class instanceof Name) {
							continue 2;
						}
						if (!$armCond->name instanceof Identifier) {
							continue 2;
						}
						$fetchedClassName = $scope->resolveName($armCond->class);
						$loweredFetchedClassName = strtolower($fetchedClassName);
						if (!array_key_exists($loweredFetchedClassName, $indexedEnumCases)) {
							continue 2;
						}

						$caseName = $armCond->name->toString();
						if (!array_key_exists($caseName, $indexedEnumCases[$loweredFetchedClassName])) {
							continue 2;
						}

						$conditionCases[] = $indexedEnumCases[$loweredFetchedClassName][$caseName];
						unset($unusedIndexedEnumCases[$loweredFetchedClassName][$caseName]);
					}

					$conditionCasesCount = count($conditionCases);
					if ($conditionCasesCount === 0) {
						throw new ShouldNotHappenException();
					} elseif ($conditionCasesCount === 1) {
						$conditionCaseType = $conditionCases[0];
					} else {
						$conditionCaseType = new UnionType($conditionCases);
					}

					$armScope = $matchScope->addTypeToExpression(
						$cond,
						$conditionCaseType,
					);
					$armScopesAndTypes[] = [$armScope, $armScope->getType($arm->body)];
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

				$matchScope = $matchScope->addTypeToExpression($cond, $remainingType);
			}
		}

		foreach ($arms as $arm) {
			if ($arm->conds === null) {
				if ($expr->hasAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME)) {
					$arm->body->setAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME, $expr->getAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME));
				}
				$armScopesAndTypes[] = [$matchScope, $matchScope->getType($arm->body)];
				continue;
			}

			if (count($arm->conds) === 0) {
				throw new ShouldNotHappenException();
			}

			$filteringExpr = $this->getFilteringExprForMatchArm($expr, $arm->conds);

			$filteringExprType = $matchScope->getType($filteringExpr);

			if (!$filteringExprType->isFalse()->yes()) {
				$truthyScope = $matchScope->filterByTruthyValue($filteringExpr);
				if ($expr->hasAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME)) {
					$arm->body->setAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME, $expr->getAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME));
				}
				$armScopesAndTypes[] = [$truthyScope, $truthyScope->getType($arm->body)];
			}

			$matchScope = $matchScope->filterByFalseyValue($filteringExpr);
		}

		return $armScopesAndTypes;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$deepContext = $context->enterDeep();
		$condType = $scope->getType($expr->cond);
		$condNativeType = $scope->getNativeType($expr->cond);
		$condResult = $nodeScopeResolver->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $deepContext);
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
					$matchArmBodyScope = $matchScope->addTypeToExpression(
						$expr->cond,
						$conditionCaseType,
					)->filterByTruthyValue($filteringExpr);
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
				continue;
			}

			if (count($arm->conds) === 0) {
				throw new ShouldNotHappenException();
			}

			$filteringExprs = [];
			$armCondScope = $matchScope;
			$condNodes = [];
			$armCondResultScope = $matchScope;
			$bodyScope = null;
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
				$armCondType = $this->treatPhpDocTypesAsCertain ? $armCondResultScope->getType($armCondExpr) : $armCondResultScope->getNativeType($armCondExpr);
				if ($armCondType->isTrue()->yes()) {
					$hasAlwaysTrueCond = true;
				}
				$armCondScope = $armCondResultScope->filterByFalseyValue($armCondExpr);
				if ($bodyScope === null) {
					$bodyScope = $armCondResultScope->filterByTruthyValue($armCondExpr);
				} else {
					$bodyScope = $bodyScope->mergeWith($armCondResultScope->filterByTruthyValue($armCondExpr));
				}
				$filteringExprs[] = $armCond;
			}

			$filteringExpr = $this->getFilteringExprForMatchArm($expr, $filteringExprs);
			$bodyScope ??= $matchScope->filterByTruthyValue($filteringExpr);
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
			$matchScope = $armCondScope->filterByFalseyValue($filteringExpr);
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
			$remainingType = $matchScope->getType($expr->cond);
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

		return $this->expressionResultFactory->create(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	/**
	 * @param Expr[] $conditions
	 */
	private function getFilteringExprForMatchArm(Match_ $expr, array $conditions): BinaryOp\Identical|FuncCall
	{
		if (count($conditions) === 1) {
			return new BinaryOp\Identical($expr->cond, $conditions[0]);
		}

		$items = [];
		foreach ($conditions as $filteringExpr) {
			$items[] = new Node\ArrayItem($filteringExpr);
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

			$truthyScope = $scope->filterByTruthyValue($varExpr);
			$truthyContradiction = $this->scopeHasNeverVariable($truthyScope, $boolVars);
			if (!$truthyContradiction) {
				continue;
			}

			$falseyScope = $scope->filterByFalseyValue($varExpr);
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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
