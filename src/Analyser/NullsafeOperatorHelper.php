<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;

class NullsafeOperatorHelper
{

	public static function getNullsafeShortcircuitedExpr(Expr $expr): Expr
	{
		if ($expr instanceof Expr\NullsafeMethodCall) {
			return new Expr\MethodCall(self::getNullsafeShortcircuitedExpr($expr->var), $expr->name, $expr->args);
		}

		if ($expr instanceof Expr\MethodCall) {
			$var = self::getNullsafeShortcircuitedExpr($expr->var);
			if ($expr->var === $var) {
				return $expr;
			}

			return new Expr\MethodCall($var, $expr->name, $expr->getArgs());
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			$class = self::getNullsafeShortcircuitedExpr($expr->class);
			if ($expr->class === $class) {
				return $expr;
			}

			return new Expr\StaticCall($class, $expr->name, $expr->getArgs());
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			$var = self::getNullsafeShortcircuitedExpr($expr->var);
			if ($expr->var === $var) {
				return $expr;
			}

			return new Expr\ArrayDimFetch($var, $expr->dim);
		}

		if ($expr instanceof Expr\NullsafePropertyFetch) {
			return new Expr\PropertyFetch(self::getNullsafeShortcircuitedExpr($expr->var), $expr->name);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			$var = self::getNullsafeShortcircuitedExpr($expr->var);
			if ($expr->var === $var) {
				return $expr;
			}

			return new Expr\PropertyFetch($var, $expr->name);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			$class = self::getNullsafeShortcircuitedExpr($expr->class);
			if ($expr->class === $class) {
				return $expr;
			}

			return new Expr\StaticPropertyFetch($class, $expr->name);
		}

		return $expr;
	}

}
