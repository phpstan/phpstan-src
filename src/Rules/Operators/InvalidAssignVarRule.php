<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Expr>
 */
class InvalidAssignVarRule implements Rule
{

	public function __construct(private NullsafeCheck $nullsafeCheck)
	{
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
			&& !$node instanceof AssignRef
		) {
			return [];
		}

		if ($this->nullsafeCheck->containsNullSafe($node->var)) {
			return [
				RuleErrorBuilder::message('Nullsafe operator cannot be on left side of assignment.')
					->identifier('nullsafe.assign')
					->nonIgnorable()
					->build(),
			];
		}

		if ($node instanceof AssignRef && $this->nullsafeCheck->containsNullSafe($node->expr)) {
			return [
				RuleErrorBuilder::message('Nullsafe operator cannot be on right side of assignment by reference.')
					->identifier('nullsafe.byRef')
					->nonIgnorable()
					->build(),
			];
		}

		if ($this->containsNonAssignableExpression($node->var)) {
			return [
				RuleErrorBuilder::message('Expression on left side of assignment is not assignable.')
					->identifier('assign.invalidExpr')
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

	private function containsNonAssignableExpression(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable) {
			return false;
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return false;
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return false;
		}

		if ($expr instanceof Expr\StaticPropertyFetch) {
			return false;
		}

		if ($expr instanceof Expr\List_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}
				if (!$this->containsNonAssignableExpression($item->value)) {
					continue;
				}

				return true;
			}

			return false;
		}

		return true;
	}

}
