<?php

namespace ExpressionTypeResolverExtension;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class MethodCallReturnsBoolExpressionTypeResolverExtension implements ExpressionTypeResolverExtension {

	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if (!$expr instanceof MethodCall) {
			return null;
		}

		if (!$expr->name instanceof Identifier) {
			return null;
		}

		if ($expr->name->name !== 'methodReturningBoolNoMatterTheCallerUnlessReturnsString') {
			return null;
		}

		$methodReflection = $scope->getMethodReflection($scope->getType($expr->var), $expr->name->name);

		if ($methodReflection === null) {
			return null;
		}

		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$expr->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();

		if ($returnType instanceof StringType) {
			return null;
		}

		return new BooleanType();
	}

}
