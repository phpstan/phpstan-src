<?php

namespace ExpressionTypeResolverExtension;

use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\GlobalVariableExpr;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PhpParser\Node\Expr;

class GlobalExpressionTypeResolverExtension implements ExpressionTypeResolverExtension {

	public function getType(Expr $expr, Scope $scope): ?Type
	{

		if (!$expr instanceof GlobalVariableExpr) {
			return null;
		}

		$variableName = $expr->getVar()->name;

		if ($variableName === 'MY_GLOBAL_BOOL') {
			return new BooleanType();
		}

		if ($variableName === 'MY_GLOBAL_INT') {
			return new IntegerType();
		}

		if ($variableName === 'MY_GLOBAL_STR') {
			return new StringType();
		}

		if ($variableName === 'MY_GLOBAL_ARRAY') {
			return new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true));
		}

		return null;
	}

}
