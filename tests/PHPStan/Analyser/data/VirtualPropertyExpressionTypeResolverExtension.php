<?php

namespace ExpressionTypeResolverExtension;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class VirtualPropertyExpressionTypeResolverExtension implements ExpressionTypeResolverExtension {

	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if (!$expr instanceof PropertyFetch) {
			return null;
		}

		if (!$expr->name instanceof Identifier || $expr->name->name !== 'virtualProperty') {
			return null;
		}

		if ($scope->hasExpressionType($expr)->yes()) {
			return null;
		}

		$classType = new ObjectType('ExpressionTypeResolverExtensionTest\ClassWithVirtualProperty');
		if (!$classType->isSuperTypeOf($scope->getType($expr->var))->yes()) {
			return null;
		}

		return new UnionType([new StringType(), new NullType()]);
	}

}
