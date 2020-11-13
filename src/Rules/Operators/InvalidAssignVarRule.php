<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Expr>
 */
class InvalidAssignVarRule implements Rule
{

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

		if ($this->containsNullSafe($node->var)) {
			return [
				RuleErrorBuilder::message('Nullsafe operator cannot be on left side of assignment.')->nonIgnorable()->build(),
			];
		}

		if ($this->containsNonAssignableExpression($node->var)) {
			return [
				RuleErrorBuilder::message('Expression on left side of assignment is not assignable.')->nonIgnorable()->build(),
			];
		}

		return [];
	}

	private function containsNullSafe(Expr $expr): bool
	{
		if (
			$expr instanceof Expr\NullsafePropertyFetch
			|| $expr instanceof Expr\NullsafeMethodCall
		) {
			return true;
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->containsNullSafe($expr->var);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->containsNullSafe($expr->var);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->containsNullSafe($expr->class);
		}

		if ($expr instanceof Expr\MethodCall) {
			return $this->containsNullSafe($expr->var);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->containsNullSafe($expr->class);
		}

		if ($expr instanceof Expr\List_ || $expr instanceof Expr\Array_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				if ($item->key !== null && $this->containsNullSafe($item->key)) {
					return true;
				}

				if ($this->containsNullSafe($item->value)) {
					return true;
				}
			}
		}

		return false;
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

		if ($expr instanceof Expr\List_ || $expr instanceof Expr\Array_) {
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
