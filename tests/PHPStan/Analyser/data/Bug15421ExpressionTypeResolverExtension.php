<?php

namespace ExpressionTypeResolverExtension;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class Bug15421ExpressionTypeResolverExtension implements ExpressionTypeResolverExtension
{

	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if ($expr instanceof PropertyFetch
			&& $expr->name instanceof Identifier
			&& $expr->name->name === 'holder'
			&& (new ObjectType('Bug15421\Crate'))->isSuperTypeOf($scope->getType($expr->var))->yes()) {
			return new ObjectType('Bug15421\Holder');
		}

		return null;
	}

}
