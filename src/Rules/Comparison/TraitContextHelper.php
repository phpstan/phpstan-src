<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use function in_array;
use function is_array;

final class TraitContextHelper
{

	/**
	 * Checks if a comparison expression's result may differ across different
	 * class contexts when used inside a trait.
	 *
	 * Uses a deep traversal to find $this, self::, static::, or parent:: references
	 * in any sub-expression.
	 */
	public static function isBinaryOpDependentOnTraitContext(Scope $scope, Expr $left, Expr $right): bool
	{
		if (!$scope->isInTrait()) {
			return false;
		}

		return self::containsThisDependentExpression($left)
			|| self::containsThisDependentExpression($right);
	}

	private static function containsThisDependentExpression(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable) {
			return $expr->name === 'this';
		}

		if (
			($expr instanceof Expr\StaticPropertyFetch || $expr instanceof Expr\StaticCall)
			&& $expr->class instanceof Name
		) {
			$className = $expr->class->toString();
			if (in_array($className, ['self', 'static', 'parent'], true)) {
				return true;
			}
		}

		foreach ($expr->getSubNodeNames() as $name) {
			$subNode = $expr->$name;
			if ($subNode instanceof Expr) {
				if (self::containsThisDependentExpression($subNode)) {
					return true;
				}
			} elseif (is_array($subNode)) {
				foreach ($subNode as $item) {
					if ($item instanceof Expr && self::containsThisDependentExpression($item)) {
						return true;
					}
					if ($item instanceof Arg && self::containsThisDependentExpression($item->value)) {
						return true;
					}
				}
			}
		}

		return false;
	}

}
