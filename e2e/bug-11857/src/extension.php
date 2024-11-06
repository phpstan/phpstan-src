<?php

namespace Bug11857;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PhpParser\Node\Expr\MethodCall;

class RelationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return Model::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'belongsTo';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
		$returnType = $methodReflection->getVariants()[0]->getReturnType();
		$argType    = $scope->getType($methodCall->getArgs()[0]->value);
		$modelClass = $argType->getClassStringObjectType()->getObjectClassNames()[0];

		return new GenericObjectType($returnType->getObjectClassNames()[0], [
			new ObjectType($modelClass),
			$scope->getType($methodCall->var),
		]);
	}
}

