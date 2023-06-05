<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function count;

final class DsMapDynamicMethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === 'Ds\Map'
			&& ($methodReflection->getName() === 'get' || $methodReflection->getName() === 'remove');
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
	{
		if (count($methodCall->args) < 2) {
			return $methodReflection->getThrowType();
		}

		return new VoidType();
	}

}
