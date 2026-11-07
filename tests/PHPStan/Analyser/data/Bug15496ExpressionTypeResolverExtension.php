<?php

namespace Bug15496;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Types `Order::$lines` as array<int, Line>, and `$fallbackLines` fetched on
 * an object of unknown class as array<int, Line>; declines everything else.
 */
final class Bug15496ExpressionTypeResolverExtension implements ExpressionTypeResolverExtension
{
	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if (!$expr instanceof Expr\PropertyFetch || !$expr->name instanceof Identifier) {
			return null;
		}
		if ($expr->name->name === 'fallbackLines') {
			$varType = $scope->getType($expr->var);
			if (!$varType->isObject()->yes() || $varType->getObjectClassNames() !== []) {
				return null;
			}
			return new ArrayType(new IntegerType(), new ObjectType(Line::class));
		}
		if ($expr->name->name !== 'lines') {
			return null;
		}
		if (!(new ObjectType(Order::class))->isSuperTypeOf($scope->getType($expr->var))->yes()) {
			return null;
		}
		return new ArrayType(new IntegerType(), new ObjectType(Line::class));
	}
}
