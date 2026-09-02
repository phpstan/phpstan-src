<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\BooleanNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<BooleanOr|LogicalOr>
 */
#[AutowiredService]
final class BooleanOrHandler implements ExprHandler
{

	public function __construct(
		private BooleanNarrowingHelper $booleanNarrowingHelper,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BooleanOr || $expr instanceof LogicalOr;
	}

	/**
	 * For `if ($a || $b)` truthy, expressions narrowed by stored conditional
	 * holders (e.g. `$a = $obj instanceof ClassA;` records "when `$a` is
	 * truthy, `$obj` is `ClassA`") need to be projected into the OR-truthy
	 * scope as the union of the per-arm narrowings. specifyTypesInCondition
	 * for each arm only looks at the boolean variable itself, so the held
	 * narrowing of `$obj` would otherwise be invisible until a later check
	 * pins one of the booleans down.
	 *
	 * For each conditional-holder target $T:
	 * - resolve $T's type in the left-truthy and right-truthy filtered scopes
	 * - if both narrow $T strictly below the original, add `$T : leftT|rightT`
	 *   as a sure type to the OR-truthy result
	 *
	 * The asymmetric case (one arm narrows, the other doesn't) is intentionally
	 * skipped: in the OR-truthy scope the arm that didn't narrow could still be
	 * the truthy one, so the sound result is the original (unnarrowed) type.
	 */
	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$leftFalseyScope = $leftResult->getFalseyScope();
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftFalseyScope, $storage, $nodeCallback, $context);
		$rightExprType = $rightResult->getType();
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$leftMergedWithRightScope = $leftResult->getTruthyScope();
		} else {
			$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
		}

		$result = $this->expressionResultFactory->create(
			$leftMergedWithRightScope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			// || is falsey only when the right side was evaluated (on the left-falsey
			// scope) and is itself falsey - that is exactly the right operand's falsey
			// scope: it carries the left narrowing and the right's by-ref/side-effect
			// definitions, and does not re-apply the left narrowing over a variable the
			// right operand reassigned (bug-9400).
			falseyScopeOverrideResult: $rightResult,
			typeCallback: static function (bool $nativeTypesPromoted) use ($leftResult, $rightResult): Type {
				$leftBooleanType = ($nativeTypesPromoted ? $leftResult->getNativeType() : $leftResult->getType())->toBoolean();
				if ($leftBooleanType->isTrue()->yes()) {
					return new ConstantBooleanType(true);
				}

				// the right side was processed on the left-falsey scope including
				// the left's side effects (assignments, by-ref writes) - that
				// captured scope is the evaluation point, no re-walk and no
				// depth cap needed
				$rightBooleanType = ($nativeTypesPromoted ? $rightResult->getNativeType() : $rightResult->getType())->toBoolean();
				if ($rightBooleanType->isTrue()->yes()) {
					return new ConstantBooleanType(true);
				}

				if (
					$leftBooleanType->isFalse()->yes()
					&& $rightBooleanType->isFalse()->yes()
				) {
					return new ConstantBooleanType(false);
				}

				return new BooleanType();
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->booleanNarrowingHelper->specifyDisjunction(
				$nodeScopeResolver,
				$nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope,
				$context,
				$expr,
				$expr->left,
				static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $leftResult->getSpecifiedTypesForScope($scope, $ctx),
				static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $leftResult->getNativeType() : $leftResult->getType(),
				static fn (): MutatingScope => $leftResult->getTruthyScope(),
				static fn (): MutatingScope => $leftResult->getFalseyScope(),
				$expr->right,
				static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $rightResult->getSpecifiedTypesForScope($scope, $ctx),
				static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $rightResult->getNativeType() : $rightResult->getType(),
				static fn (): MutatingScope => $rightResult->getTruthyScope(),
			),
		);
		// store before emitting the virtual node: its rules ask about the raw
		// expression, and a synchronously invoked rule (the plain resolver,
		// PHP < 8.1) must find the result in the storage instead of re-walking
		// it on demand; processExprNodeInternal()'s later store is a no-op
		$nodeScopeResolver->storeExpressionResult($storage, $expr, $result);
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new BooleanOrNode($expr, $leftFalseyScope), $scope, $storage, $context);

		return $result;
	}

}
