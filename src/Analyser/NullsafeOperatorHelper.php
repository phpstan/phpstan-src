<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\TypeCombinator;

final class NullsafeOperatorHelper
{

	public static function getNullsafeShortcircuitedExprRespectingScope(Scope $scope, Expr $expr): Expr
	{
		if (!TypeCombinator::containsNull($scope->getType($expr))) {
			// We're in most likely in context of a null-safe operator ($scope->moreSpecificType is defined for $expr)
			// Modifying the expression would not bring any value or worse ruin the context information
			return $expr;
		}

		return self::getNullsafeShortcircuitedExpr($expr);
	}

	/**
	 * @internal Use NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope
	 */
	public static function getNullsafeShortcircuitedExpr(Expr $expr): Expr
	{
		// Collect the chain of chained-access wrappers (outermost first) walking inward.
		$chain = [];
		$current = $expr;

		while (true) {
			if (
				$current instanceof Expr\NullsafeMethodCall
				|| $current instanceof Expr\MethodCall
				|| $current instanceof Expr\ArrayDimFetch
				|| $current instanceof Expr\NullsafePropertyFetch
				|| $current instanceof Expr\PropertyFetch
			) {
				$chain[] = $current;
				$current = $current->var;
			} elseif (
				($current instanceof Expr\StaticCall || $current instanceof Expr\StaticPropertyFetch)
				&& $current->class instanceof Expr
			) {
				$chain[] = $current;
				$current = $current->class;
			} else {
				break;
			}
		}

		// Rebuild from innermost outward, replacing Nullsafe* nodes as we go.
		$result = $current;
		$changed = false;

		foreach (array_reverse($chain) as $node) {
			if ($node instanceof Expr\NullsafeMethodCall) {
				$result = new Expr\MethodCall($result, $node->name, $node->args);
				$changed = true;
			} elseif ($node instanceof Expr\NullsafePropertyFetch) {
				$result = new Expr\PropertyFetch($result, $node->name);
				$changed = true;
			} elseif (!$changed) {
				// Nothing has changed yet — keep the original node to preserve identity.
				$result = $node;
			} elseif ($node instanceof Expr\MethodCall) {
				$result = new Expr\MethodCall($result, $node->name, $node->getArgs());
			} elseif ($node instanceof Expr\ArrayDimFetch) {
				$result = new Expr\ArrayDimFetch($result, $node->dim);
			} elseif ($node instanceof Expr\PropertyFetch) {
				$result = new Expr\PropertyFetch($result, $node->name);
			} elseif ($node instanceof Expr\StaticCall) {
				$result = new Expr\StaticCall($result, $node->name, $node->getArgs());
			} elseif ($node instanceof Expr\StaticPropertyFetch) {
				$result = new Expr\StaticPropertyFetch($result, $node->name);
			}
		}

		return $result;
	}

}
