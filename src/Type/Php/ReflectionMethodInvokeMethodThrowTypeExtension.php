<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use function in_array;

#[AutowiredService]
final class ReflectionMethodInvokeMethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getDeclaringClass()->getName(), [ReflectionMethod::class, ReflectionFunction::class], true)
			&& in_array($methodReflection->getName(), ['invoke', 'invokeArgs'], true);
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		return new ObjectType(Throwable::class);
	}

}
