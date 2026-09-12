<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CoalesceExpressionNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function array_filter;
use function sprintf;

/**
 * @implements Rule<CoalesceExpressionNode>
 */
#[RegisteredRule(level: 1)]
final class NullCoalesceRule implements Rule
{

	public function __construct(
		private IssetCheck $issetCheck,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		#[AutowiredParameter(ref: '%featureToggles.unnecessaryNullCoalesce%')]
		private bool $unnecessaryNullCoalesce,
	)
	{
	}

	public function getNodeType(): string
	{
		return CoalesceExpressionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$subjectResult = $node->getSubjectResult();
		$error = $this->issetCheck->check(
			$subjectResult,
			$scope,
			$node->getOperatorDescription(),
			'nullCoalesce',
			static function (Type $type): ?string {
				$isNull = $type->isNull();
				if ($isNull->maybe()) {
					return null;
				}

				if ($isNull->yes()) {
					return 'is always null';
				}

				return 'is not nullable';
			},
		) ?? $this->checkUnnecessaryNullCoalesce($node, $scope);

		if ($error === null) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $subjectResult->getExpr());
			return [];
		}

		if ($scope->isInTrait()) {
			// The error messages already distinguish the possible outcomes,
			// so the contexts only need to be told apart by error/no error.
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $subjectResult->getExpr(), true, $error);
			return [];
		}

		return [$error];
	}

	private function checkUnnecessaryNullCoalesce(CoalesceExpressionNode $node, Scope $scope): ?IdentifierRuleError
	{
		if (!$this->unnecessaryNullCoalesce) {
			return null;
		}

		$originalExpr = $node->getOriginalExpr();
		if ($originalExpr instanceof Node\Expr\BinaryOp\Coalesce) {
			$right = $originalExpr->right;
			$operator = '??';
		} elseif ($originalExpr instanceof Node\Expr\AssignOp\Coalesce) {
			$right = $originalExpr->expr;
			$operator = '??=';
		} else {
			return null;
		}

		if (!$scope->getType($right)->isNull()->yes()) {
			return null;
		}

		// Dropping the coalesce also drops the evaluation of the right side, which
		// only happens when the left side is null. That's only observationally
		// equivalent when evaluating the right side cannot do anything but produce
		// its null value.
		$rightResult = $node->getRightResult();
		if (
			$rightResult->getImpurePoints() !== []
			|| array_filter($rightResult->getThrowPoints(), static fn (InternalThrowPoint $throwPoint): bool => $throwPoint->isExplicit()) !== []
			|| $rightResult->hasYield()
			|| $this->containsAssign($right)
		) {
			return null;
		}

		// The coalesce only changes the result when the left side is undefined.
		// If the left side is always set, `?? null` (or `??= null`) never changes
		// anything, so the whole coalesce is redundant.
		$resolution = $node->getSubjectResult()->getIssetabilityResolution($scope->toWalkScope(), false);
		if ($resolution->isSet(static fn (): bool => true) !== true) {
			return null;
		}

		return RuleErrorBuilder::message(
			sprintf('Coalesce operator %s is unnecessary because the left side is always set and the right side is null.', $operator),
		)->identifier('nullCoalesce.unnecessary')->build();
	}

	/**
	 * Writes to variables, properties and offsets are not impure points, but they
	 * still make the right side worth keeping around.
	 */
	private function containsAssign(Node\Expr $expr): bool
	{
		return (new NodeFinder())->findFirst([$expr], static fn (Node $node): bool => $node instanceof Node\Expr\Assign
			|| $node instanceof Node\Expr\AssignRef
			|| $node instanceof Node\Expr\AssignOp
			|| $node instanceof Node\Expr\PostInc
			|| $node instanceof Node\Expr\PreInc
			|| $node instanceof Node\Expr\PostDec
			|| $node instanceof Node\Expr\PreDec) !== null;
	}

}
