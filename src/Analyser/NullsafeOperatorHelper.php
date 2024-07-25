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
