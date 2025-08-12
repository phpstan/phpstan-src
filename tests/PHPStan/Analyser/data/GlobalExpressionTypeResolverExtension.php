<?php

namespace ExpressionTypeResolverExtension;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class GlobalExpressionTypeResolverExtension implements ExpressionTypeResolverExtension {

	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if (
			!$expr instanceof Variable
			|| !\is_string($expr->name)
			|| !$scope->isGlobalVariable($expr->name)
		) {
			return null;
		}

		if ($expr->name === 'MY_GLOBAL_BOOL') {
			return new BooleanType();
		}

		if ($expr->name === 'MY_GLOBAL_INT') {
			return new IntegerType();
		}

		if ($expr->name === 'MY_GLOBAL_STR') {
			return new StringType();
		}

		return null;
	}

}
