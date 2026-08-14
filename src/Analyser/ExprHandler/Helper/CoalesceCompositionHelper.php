<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Composes the `??` type and narrowing from the sides' walk results - shared
 * by CoalesceHandler and AssignOpHandler's `??=`, which has no real Coalesce
 * node to walk.
 */
#[AutowiredService]
final class CoalesceCompositionHelper
{

	public function __construct(
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	/**
	 * A falsey coalesce means its left side was null (when it was surely set).
	 * The issetability verdict runs on the evaluation scope (where the left
	 * side was walked); the asking scope is only the conduit for the left
	 * side's narrowing fan-out. This is the narrowing of the coalesce's VALUE
	 * being falsey/false - not of the left side being unset, so it must not
	 * reduce isset certainty.
	 */
	public function getFalseySpecifiedTypes(MutatingScope $s, MutatingScope $evaluationScope, Expr $leftExpr, ExpressionResult $leftResult, Expr $rootExpr, TypeSpecifierContext $context): SpecifiedTypes
	{
		$isset = $leftResult->getIssetabilityResolution($evaluationScope, false)->isSet(static fn (): bool => true);

		if ($isset !== true) {
			return new SpecifiedTypes();
		}

		return $this->defaultNarrowingHelper->createSubjectTypes($s, $leftExpr, $leftResult, new NullType(), $context->negate())->setRootExpr($rootExpr);
	}

	/**
	 * The right side of a coalesce only evaluates when the left side is null
	 * or unset - the falsey narrowing of isset($leftExpr) composed from the
	 * left read (a certainty reduction for surely-set non-nullable subjects,
	 * not a bare null pin - mirrors filtering the right-side scope by falsey
	 * `isset()` instead of `!== null`).
	 *
	 * @param array<int, ExpressionResult> $chainResults
	 */
	public function getRightSideScopeSpecifiedTypes(MutatingScope $s, Expr $leftExpr, ExpressionResult $leftResult, array $chainResults, Expr $rootExpr): SpecifiedTypes
	{
		return $this->defaultNarrowingHelper->createIssetSingleSubjectNonTrueTypes(
			$s,
			$leftExpr,
			$leftResult,
			$this->defaultNarrowingHelper->buildChainTypeReader($chainResults, $s),
			TypeSpecifierContext::createFalsey(),
			$rootExpr,
		);
	}

	/**
	 * The `??`'s own type: the left side when it is surely set and non-null,
	 * the right side when it surely is not, their union otherwise. Runs on the
	 * evaluation scope (where the sides were walked), not the asking scope.
	 *
	 * @param array<int, ExpressionResult> $chainResults
	 */
	public function composeType(
		NodeScopeResolver $nodeScopeResolver,
		Expr $leftExpr,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		MutatingScope $evaluationScope,
		array $chainResults,
		Expr $rootExpr,
		bool $nativeTypesPromoted,
	): Type
	{
		// the whole resolution runs in the asked flavour - the native ask maps
		// the evaluation scope once and every read below follows it, so the
		// phpdoc left type never leaks into the native answer
		if ($nativeTypesPromoted) {
			$evaluationScope = $evaluationScope->doNotTreatPhpDocTypesAsCertain();
		}
		$result = $leftResult->getIssetabilityResolution($evaluationScope, $nativeTypesPromoted)->isSet(static function (Type $type): ?bool {
			$isNull = $type->isNull();
			if ($isNull->maybe()) {
				return null;
			}

			return !$isNull->yes();
		});

		// the left side's type when it is set: the left read on the left-is-set
		// narrowed scope (offsets resolve against the HasOffset-narrowed parent).
		// The narrowing is tracked by the scope (getTypeOnScope's authoritative
		// read); only an untracked left side needs reprocessing there.
		$leftIsSetType = function () use ($leftExpr, $leftResult, $nodeScopeResolver, $evaluationScope, $chainResults, $rootExpr, $nativeTypesPromoted): Type {
			$leftIssetTypes = $this->defaultNarrowingHelper->createIssetTruthyChainTypes(
				$evaluationScope,
				$leftExpr,
				$this->defaultNarrowingHelper->buildChainTypeReader($chainResults, $evaluationScope),
				$rootExpr,
				TypeSpecifierContext::createTruthy(),
			);
			$leftIsSetScope = $evaluationScope->applySpecifiedTypes($leftIssetTypes);
			$leftType = $leftResult->answersOnScope($leftIsSetScope, $nativeTypesPromoted)
				? $leftResult->getTypeOnScope($leftIsSetScope, $nativeTypesPromoted)
				: $nodeScopeResolver->processExprOnDemand($leftExpr, $leftIsSetScope, new ExpressionResultStorage())->getTypeOnScope($leftIsSetScope, $nativeTypesPromoted);

			return TypeCombinator::removeNull($leftType);
		};

		if ($result !== null && $result !== false) {
			return $leftIsSetType();
		}

		// the right side was processed on the left-is-null scope, so its own
		// result is the evaluation point.
		$rightType = $nativeTypesPromoted ? $rightResult->getNativeType() : $rightResult->getType();

		if ($result === null) {
			return TypeCombinator::union($leftIsSetType(), $rightType);
		}

		return $rightType;
	}

}
