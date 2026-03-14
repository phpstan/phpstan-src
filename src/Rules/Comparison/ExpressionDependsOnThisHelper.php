<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Expr;

final class ExpressionDependsOnThisHelper
{

	public static function isExpressionDependentOnThis(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable && $expr->name === 'this') {
			return true;
		}

		if ($expr instanceof Expr\PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
			return self::isExpressionDependentOnThis($expr->var);
		}

		if ($expr instanceof Expr\MethodCall || $expr instanceof Expr\NullsafeMethodCall) {
			return self::isExpressionDependentOnThis($expr->var);
		}

		if ($expr instanceof Expr\StaticPropertyFetch || $expr instanceof Expr\StaticCall) {
			if ($expr->class instanceof Expr) {
				return self::isExpressionDependentOnThis($expr->class);
			}

			$className = $expr->class->toString();
			return in_array($className, ['self', 'static', 'parent'], true);
		}

		return false;
	}

}
