<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class NullsafeShortCircuitingHelper
{

	public static function getType(MutatingScope $scope, Expr $expr, Type $type): Type
	{
		while (true) {
			if ($expr instanceof NullsafePropertyFetch || $expr instanceof NullsafeMethodCall) {
				$varType = $scope->getType($expr->var);
				if (TypeCombinator::containsNull($varType)) {
					return TypeCombinator::addNull($type);
				}

				return $type;
			}

			if ($expr instanceof ArrayDimFetch || $expr instanceof PropertyFetch || $expr instanceof MethodCall) {
				$expr = $expr->var;
				continue;
			}

			if (($expr instanceof StaticPropertyFetch || $expr instanceof StaticCall) && $expr->class instanceof Expr) {
				$expr = $expr->class;
				continue;
			}

			return $type;
		}
	}

}
