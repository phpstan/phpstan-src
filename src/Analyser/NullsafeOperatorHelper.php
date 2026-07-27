<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\TypeCombinator;
use function array_reverse;

final class NullsafeOperatorHelper
{

	private const SHORTCIRCUITED_ATTRIBUTE = 'phpstan_nullsafeShortcircuited';

	public static function getNullsafeShortcircuitedExprRespectingScope(Scope $scope, Expr $expr): Expr
	{
		$shortcircuitedExpr = self::getNullsafeShortcircuitedExpr($expr);
		if ($shortcircuitedExpr === $expr) {
			// No nullsafe operator anywhere in the expression - the result is $expr
			// either way, so skip asking the scope for the expression's type.
			return $expr;
		}

		if (!TypeCombinator::containsNull($scope->getType($expr))) {
			// We're in most likely in context of a null-safe operator ($scope->moreSpecificType is defined for $expr)
			// Modifying the expression would not bring any value or worse ruin the context information
			return $expr;
		}

		return $shortcircuitedExpr;
	}

	/**
	 * @internal Use NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope
	 */
	public static function getNullsafeShortcircuitedExpr(Expr $expr): Expr
	{
		if ($expr->getAttribute(self::SHORTCIRCUITED_ATTRIBUTE) === true) {
			return $expr;
		}

		// Look for a nullsafe operator before building anything: without one the
		// result is $expr itself, and this walk allocates nothing, where
		// collecting and rebuilding the chain below pays for an array, an
		// array_reverse() and a loop on every call.
		$current = $expr;
		while (
			!$current instanceof Expr\NullsafeMethodCall
			&& !$current instanceof Expr\NullsafePropertyFetch
		) {
			$next = self::chainedInto($current);
			if ($next === null) {
				// Every level of a chain is resolved in turn and each one used to
				// walk down to the root again, so a chain of depth N cost O(N^2)
				// walk steps — with no nullsafe operator in it at all. Marking
				// the levels on the way out makes that O(N) per chain.
				for ($level = $expr; $level !== null; $level = self::chainedInto($level)) {
					$level->setAttribute(self::SHORTCIRCUITED_ATTRIBUTE, true);
				}

				return $expr;
			}

			$current = $next;
		}

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

			if ($result === $node) {
				// This level shortcircuits to itself — the nullsafe operator is
				// above it, not inside it. Only that answer is remembered: a
				// rebuilt expression stays freshly built, because callers compare
				// the result with === to tell whether anything was rewritten.
				$node->setAttribute(self::SHORTCIRCUITED_ATTRIBUTE, true);
			}
		}

		return $result;
	}

	/** The next level down a chained-access expression, or null at its root. */
	private static function chainedInto(Expr $expr): ?Expr
	{
		if (
			$expr instanceof Expr\NullsafeMethodCall
			|| $expr instanceof Expr\MethodCall
			|| $expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\NullsafePropertyFetch
			|| $expr instanceof Expr\PropertyFetch
		) {
			return $expr->var;
		}

		if (
			($expr instanceof Expr\StaticCall || $expr instanceof Expr\StaticPropertyFetch)
			&& $expr->class instanceof Expr
		) {
			return $expr->class;
		}

		return null;
	}

}
