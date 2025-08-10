<?php

namespace ExpressionTypeResolverExtension;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;

class GlobalExpressionTypeResolverExtension implements ExpressionTypeResolverExtension {

	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if (!$expr instanceof Variable) {
			return null;
		}

		if ($expr->name === 'MY_FRAMEWORK_GLOBAL') {
			return new BooleanType();
		}

		return null;
	}

}
