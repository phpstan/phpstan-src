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
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
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
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TernaryHandler.cpp')]
final class TernaryHandler implements ExprHandler, PerFileAnalysisResettable
{

	/**
	 * Keyed by the ternary node's spl_object_id(). The file's own AST nodes
	 * live for the whole analysis (the parser cache retains them), but a
	 * synthetic node built and dropped mid-file frees its id for reuse - so
	 * each entry pins the node it was captured for and answers for that very
	 * node only; the per-file reset empties the map.
	 *
	 * @var array<int, array{Ternary, ExpressionResult, ExpressionResult, ExpressionResult}>
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
		$entry = $this->capturedResults[spl_object_id($expr)] ?? null;
		if ($entry === null || $entry[0] !== $expr) {
			return null;
		}

		return [$entry[1], $entry[2], $entry[3]];
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
			$this->capturedResults[spl_object_id($expr)] = [$expr, $ternaryCondResult, $ifResult, $elseResult];
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

		$finalScope = $finalScope->addTemplateArgumentConstraints($ifTrueScope->getTemplateArgumentConstraints())
			->addTemplateArgumentConstraints($ifFalseScope->getTemplateArgumentConstraints());

		// lazily memoized merged-falsey scope of the (cond && if) disjunct
		$aFalseyScope = null;

		return $this->expressionResultFactory->create(
			$finalScope,
			beforeScope: $scope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($ternaryCondResult->getVariableFlow(), VariableFlow::choice($ifResult !== null ? $ifResult->getVariableFlow() : null, $elseResult->getVariableFlow())),
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

				// An exact context (`=== true`, `!== false`, ...) asks about the taken
				// arm's value, not its truthiness - `0 !== false` holds although `0` is
				// falsey. The decomposition then holds for the arms compared to the
				// constant (`(cond && if !== false) || ...`) being true, the condition
				// still read by truthiness.
				$armContext = null;
				if ($context !== TypeSpecifierContext::createTruthy() && $context !== TypeSpecifierContext::createFalsey()) {
					// `=== false` / `!== true` of an arm are false when the arm is
					// true: a disjunction arm would be split as if it were negated
					if (
						!$context->true()
						&& (self::isDisjunction($expr->if ?? $expr->cond) || self::isDisjunction($expr->else))
					) {
						return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
					}
					$armContext = $context;
					$context = TypeSpecifierContext::createTruthy();
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
				[$elseTypes, $elseType, $elseTruthyScope, $elseFalseyScope] = $this->createArmOperand($expr->else, $elseResult, $elseProcessingScope, $elseResult->getScope(), $armContext);

				// the decomposition's branch scopes are the operand walks' own
				// memoized branch scopes (the evaluation points), not ask-derived;
				// thunked so deep chains do not derive every level eagerly
				$condTruthyScope = static fn (): MutatingScope => $ternaryCondResult->getTruthyScope();
				$condFalseyScope = static fn (): MutatingScope => $ternaryCondResult->getFalseyScope();

				// right disjunct: !cond && else
				$bNode = new BooleanAnd($notCondNode, $expr->else);
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
					$elseFalseyScope,
				);
				$bType = $andVerdict($notCondType, $elseType);

				// the short ternary's truthy value is the condition itself - in an
				// exact context it is compared like an arm: cond && (cond === true)
				$shortTernaryArm = $armContext !== null && ($ifResult === null || $expr->if === null);
				if (($ifResult !== null && $expr->if !== null) || $shortTernaryArm) {
					// left disjunct: cond && if
					if ($ifResult !== null && $expr->if !== null) {
						$ifExpr = $expr->if;
						[$ifTypes, $ifType, $ifTruthyScope, $ifFalseyScope] = $this->createArmOperand($expr->if, $ifResult, $ifProcessingScope, $ifResult->getScope(), $armContext);
					} else {
						$ifExpr = $expr->cond;
						[$ifTypes, $ifType, $ifTruthyScope, $ifFalseyScope] = $this->createArmOperand($expr->cond, $ternaryCondResult, $ifProcessingScope, $ifProcessingScope, $armContext);
					}
					$aNode = new BooleanAnd($expr->cond, $ifExpr);
					$aTypes = fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $this->booleanNarrowingHelper->specifyConjunction(
						$nodeScopeResolver,
						$scope,
						$ctx,
						$aNode,
						$expr->cond,
						$condTypes,
						$condTruthyScope,
						$condFalseyScope,
						$ifExpr,
						$ifTypes,
						$ifFalseyScope,
					);
					$aType = $andVerdict($condType, $ifType);
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
						$ifTruthyScope,
						$aFalseyScopeThunk,
						$bNode,
						$bTypes,
						$bType,
						$elseTruthyScope,
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
					$elseTruthyScope,
				)->setRootExpr($expr);
			},
		);
	}

	private static function isDisjunction(Expr $expr): bool
	{
		return $expr instanceof Expr\BinaryOp\BooleanOr || $expr instanceof Expr\BinaryOp\LogicalOr;
	}

	/**
	 * An arm as an operand of the (cond && if) || (!cond && else) decomposition:
	 * its narrowing, verdict and branch scopes. With an exact context the operand
	 * is the arm compared to the bool constant, narrowed like the comparison
	 * node would be - the constant pinned onto the arm plus the arm's own
	 * narrowing in the bool context - with branch scopes derived from the arm's
	 * evaluation point.
	 *
	 * @return array{callable(MutatingScope, TypeSpecifierContext): SpecifiedTypes, callable(bool): Type, callable(): MutatingScope, callable(): MutatingScope}
	 */
	private function createArmOperand(Expr $armExpr, ExpressionResult $armResult, MutatingScope $processingScope, MutatingScope $evaluatedScope, ?TypeSpecifierContext $armContext): array
	{
		if ($armContext === null) {
			return [
				static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $armResult->getSpecifiedTypesForScope($scope, $ctx),
				static fn (bool $nativeTypesPromoted): Type => $armResult->getTypeOnScope($processingScope, $nativeTypesPromoted),
				static fn (): MutatingScope => $armResult->getTruthyScope(),
				static fn (): MutatingScope => $armResult->getFalseyScope(),
			];
		}

		// `!== true` / `!== false` are the mixed contexts, `=== true` / `=== false` the pure ones
		$isIdentical = !($armContext->truthy() && $armContext->falsey());
		$value = $isIdentical ? $armContext->true() : !$armContext->true();

		$types = function (MutatingScope $scope, TypeSpecifierContext $ctx) use ($armExpr, $armResult, $isIdentical, $value): SpecifiedTypes {
			$identicalContext = $isIdentical ? $ctx : $ctx->negate();
			$types = $this->defaultNarrowingHelper->createSubjectTypes($scope, $armExpr, $armResult, new ConstantBooleanType($value), $identicalContext);

			// a nullsafe chain that did not produce the constant may have
			// short-circuited instead
			if (!$identicalContext->true() && ($armExpr instanceof Expr\NullsafeMethodCall || $armExpr instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			$boolContext = $value ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();

			return $types->unionWith($armResult->getSpecifiedTypesForScope($scope, $identicalContext->true() ? $boolContext : $boolContext->negate()));
		};

		return [
			$types,
			static function (bool $nativeTypesPromoted) use ($armResult, $processingScope, $isIdentical, $value): Type {
				$armType = $armResult->getTypeOnScope($processingScope, $nativeTypesPromoted);
				$matches = $value ? $armType->isTrue() : $armType->isFalse();
				if (!$isIdentical) {
					$matches = $matches->negate();
				}
				if ($matches->yes()) {
					return new ConstantBooleanType(true);
				}
				if ($matches->no()) {
					return new ConstantBooleanType(false);
				}

				return new BooleanType();
			},
			static fn (): MutatingScope => $evaluatedScope->applySpecifiedTypes($types($evaluatedScope, TypeSpecifierContext::createTruthy())),
			static fn (): MutatingScope => $evaluatedScope->applySpecifiedTypes($types($evaluatedScope, TypeSpecifierContext::createFalsey())),
		];
	}

}
