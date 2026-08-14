<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\BooleanNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PerFileAnalysisResettable;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function spl_object_id;

/**
 * @implements ExprHandler<Ternary>
 */
#[AutowiredService]
final class TernaryHandler implements ExprHandler, PerFileAnalysisResettable
{

	/**
	 * Keyed by the ternary node's spl_object_id(). The keys are AST nodes that
	 * live for the whole file's analysis (the parser cache retains them), so
	 * ids of live entries never collide; the per-file reset empties the map
	 * before another file could reuse them.
	 *
	 * @var array<int, array{ExpressionResult, ExpressionResult, ExpressionResult}>
	 */
	private array $capturedResults = [];

	public function resetFileAnalysisState(): void
	{
		$this->capturedResults = [];
	}

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private BooleanNarrowingHelper $booleanNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Ternary;
	}

	/**
	 * The cond/if/else results captured during the walk, for the assign-time
	 * conditional holders - null for short ternaries and unwalked nodes.
	 *
	 * @return array{ExpressionResult, ExpressionResult, ExpressionResult}|null
	 */
	public function getCapturedResults(Ternary $expr): ?array
	{
		return $this->capturedResults[spl_object_id($expr)] ?? null;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$ternaryCondResult = $nodeScopeResolver->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = $ternaryCondResult->getThrowPoints();
		$impurePoints = $ternaryCondResult->getImpurePoints();
		$hasYield = $ternaryCondResult->hasYield();
		$ifTrueScope = $ternaryCondResult->getTruthyScope();
		$ifFalseScope = $ternaryCondResult->getFalseyScope();
		$ifTrueType = null;
		$ifResult = null;

		$ifProcessingScope = $ifTrueScope;
		$elseProcessingScope = $ifFalseScope;
		if ($expr->if === null) {
			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$hasYield = $hasYield || $elseResult->hasYield();
			$ifFalseScope = $elseResult->getScope();
		} else {
			$ifResult = $nodeScopeResolver->processExprNode($stmt, $expr->if, $ifTrueScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $ifResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $ifResult->getImpurePoints());
			$hasYield = $hasYield || $ifResult->hasYield();
			$ifTrueScope = $ifResult->getScope();
			$ifTrueType = $ifResult->getTypeOnScope($ifProcessingScope, false);

			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$hasYield = $hasYield || $elseResult->hasYield();
			$ifFalseScope = $elseResult->getScope();
		}

		if ($ifResult !== null) {
			$this->capturedResults[spl_object_id($expr)] = [$ternaryCondResult, $ifResult, $elseResult];
		}

		$condType = $ternaryCondResult->getType();
		if ($condType->isTrue()->yes()) {
			$finalScope = $ifTrueScope;
		} elseif ($condType->isFalse()->yes()) {
			$finalScope = $ifFalseScope;
		} else {
			if ($ifTrueType instanceof NeverType && $ifTrueType->isExplicit()) {
				$finalScope = $ifFalseScope;
			} else {
				$ifFalseType = $elseResult->getTypeOnScope($elseProcessingScope, false);

				if ($ifFalseType instanceof NeverType && $ifFalseType->isExplicit()) {
					$finalScope = $ifTrueScope;
				} else {
					$finalScope = $ifTrueScope->mergeWith($ifFalseScope);
				}
			}
		}

		// lazily memoized merged-falsey scope of the (cond && if) disjunct
		$aFalseyScope = null;

		return $this->expressionResultFactory->create(
			$finalScope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $ternaryCondResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			// the branches were processed on the cond-truthy/cond-falsey scopes
			// including the condition's side effects - those captured scopes
			// are the evaluation points, no re-walk needed. Reading the branch
			// results ON those scopes matters when processExprNode answered a
			// branch from a stored result (an on-demand ternary whose branches
			// are already-walked real nodes): the stored walk-position type
			// predates the condition's narrowing the branch scope carries.
			typeCallback: static function (bool $nativeTypesPromoted) use ($expr, $ternaryCondResult, $ifResult, $elseResult, $ifProcessingScope, $elseProcessingScope, $nodeScopeResolver): Type {
				if ($nativeTypesPromoted) {
					$ifProcessingScope = $ifProcessingScope->doNotTreatPhpDocTypesAsCertain();
				}
				$booleanConditionType = ($nativeTypesPromoted ? $ternaryCondResult->getNativeType() : $ternaryCondResult->getType())->toBoolean();
				$elseType = $elseResult->getTypeOnScope($elseProcessingScope, $nativeTypesPromoted);
				if ($expr->if === null || $ifResult === null) {
					// short-ternary truthy value: the condition read on its own truthy
					// scope. The truthy narrowing is tracked by the scope
					// (getTypeOnScope's authoritative read); only an untracked
					// condition needs reprocessing there.
					$condTruthyType = $ternaryCondResult->answersOnScope($ifProcessingScope, false)
						? $ternaryCondResult->getTypeOnScope($ifProcessingScope, false)
						: $nodeScopeResolver->processExprOnDemand($expr->cond, $ifProcessingScope, new ExpressionResultStorage())->getType();
					if ($booleanConditionType->isTrue()->yes()) {
						return $condTruthyType;
					}

					if ($booleanConditionType->isFalse()->yes()) {
						return $elseType;
					}

					return TypeCombinator::union(
						TypeCombinator::removeFalsey($condTruthyType),
						$elseType,
					);
				}

				$ifType = $ifResult->getTypeOnScope($ifProcessingScope, $nativeTypesPromoted);
				if ($booleanConditionType->isTrue()->yes()) {
					return $ifType;
				}

				if ($booleanConditionType->isFalse()->yes()) {
					return $elseType;
				}

				return TypeCombinator::union(
					$ifType,
					$elseType,
				);
			},
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $ternaryCondResult, $ifResult, $elseResult, $ifProcessingScope, $elseProcessingScope, $nodeScopeResolver, $scope, &$aFalseyScope): SpecifiedTypes {
				$s = $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope;
				if ($expr->cond instanceof Ternary || $context->null()) {
					return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
				}

				// cond ? if : else narrows like (cond && if) || (!cond && else),
				// composed from the walk's results through the boolean helpers -
				// the fabricated nodes are only printed into holder keys
				$notCondNode = new Expr\BooleanNot($expr->cond);

				$condTypes = static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $ternaryCondResult->getSpecifiedTypesForScope($scope, $ctx);
				$condType = static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $ternaryCondResult->getNativeType() : $ternaryCondResult->getType();
				$notCondTypes = static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $ternaryCondResult->getSpecifiedTypesForScope($scope, $ctx->negate());
				$notCondType = static function (bool $nativeTypesPromoted) use ($ternaryCondResult): Type {
					$bool = ($nativeTypesPromoted ? $ternaryCondResult->getNativeType() : $ternaryCondResult->getType())->toBoolean();
					if ($bool->isTrue()->yes()) {
						return new ConstantBooleanType(false);
					}
					if ($bool->isFalse()->yes()) {
						return new ConstantBooleanType(true);
					}

					return new BooleanType();
				};
				$andVerdict = static fn (callable $left, callable $right): callable => static function (bool $nativeTypesPromoted) use ($left, $right): Type {
					$leftBool = $left($nativeTypesPromoted)->toBoolean();
					$rightBool = $right($nativeTypesPromoted)->toBoolean();
					if ($leftBool->isFalse()->yes() || $rightBool->isFalse()->yes()) {
						return new ConstantBooleanType(false);
					}
					if ($leftBool->isTrue()->yes() && $rightBool->isTrue()->yes()) {
						return new ConstantBooleanType(true);
					}

					return new BooleanType();
				};
				$elseTypes = static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $elseResult->getSpecifiedTypesForScope($scope, $ctx);
				$elseType = static fn (bool $nativeTypesPromoted): Type => $elseResult->getTypeOnScope($elseProcessingScope, $nativeTypesPromoted);

				// the decomposition's branch scopes are the operand walks' own
				// memoized branch scopes (the evaluation points), not ask-derived;
				// thunked so deep chains do not derive every level eagerly
				$condTruthyScope = static fn (): MutatingScope => $ternaryCondResult->getTruthyScope();
				$condFalseyScope = static fn (): MutatingScope => $ternaryCondResult->getFalseyScope();

				// right disjunct: !cond && else
				$bNode = new BooleanAnd($notCondNode, $expr->else);
				$elseFalseyOnCondFalseyScope = static fn (): MutatingScope => $elseResult->getFalseyScope();
				$bTypes = fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $this->booleanNarrowingHelper->specifyConjunction(
					$nodeScopeResolver,
					$scope,
					$ctx,
					$bNode,
					$notCondNode,
					$notCondTypes,
					$condFalseyScope,
					$condTruthyScope,
					$expr->else,
					$elseTypes,
					$elseFalseyOnCondFalseyScope,
				);
				$bType = $andVerdict($notCondType, $elseType);
				$bTruthyScope = static fn (): MutatingScope => $elseResult->getTruthyScope();

				if ($ifResult !== null && $expr->if !== null) {
					// left disjunct: cond && if
					$aNode = new BooleanAnd($expr->cond, $expr->if);
					$ifTypes = static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $ifResult->getSpecifiedTypesForScope($scope, $ctx);
					$ifType = static fn (bool $nativeTypesPromoted): Type => $ifResult->getTypeOnScope($ifProcessingScope, $nativeTypesPromoted);
					$ifFalseyOnCondTruthyScope = static fn (): MutatingScope => $ifResult->getFalseyScope();
					$aTypes = fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $this->booleanNarrowingHelper->specifyConjunction(
						$nodeScopeResolver,
						$scope,
						$ctx,
						$aNode,
						$expr->cond,
						$condTypes,
						$condTruthyScope,
						$condFalseyScope,
						$expr->if,
						$ifTypes,
						$ifFalseyOnCondTruthyScope,
					);
					$aType = $andVerdict($condType, $ifType);
					$aTruthyScope = static fn (): MutatingScope => $ifResult->getTruthyScope();
					// the merged falsey of (cond && if) has no single walk scope -
					// derived from the evaluation point on first demand, reused across asks
					$aFalseyScopeThunk = static function () use ($scope, $aTypes, &$aFalseyScope): MutatingScope {
						return $aFalseyScope ??= $scope->applySpecifiedTypes($aTypes($scope, TypeSpecifierContext::createFalsey()));
					};

					return $this->booleanNarrowingHelper->specifyDisjunction(
						$nodeScopeResolver,
						$s,
						$context,
						$expr,
						$aNode,
						$aTypes,
						$aType,
						$aTruthyScope,
						$aFalseyScopeThunk,
						$bNode,
						$bTypes,
						$bType,
						$bTruthyScope,
					)->setRootExpr($expr);
				}

				// short ternary: cond || (!cond && else)
				return $this->booleanNarrowingHelper->specifyDisjunction(
					$nodeScopeResolver,
					$s,
					$context,
					$expr,
					$expr->cond,
					$condTypes,
					$condType,
					$condTruthyScope,
					$condFalseyScope,
					$bNode,
					$bTypes,
					$bType,
					$bTruthyScope,
				)->setRootExpr($expr);
			},
		);
	}

}
