<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use ArrayObject;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function strtolower;

#[AutowiredService]
final class ArrayObjectConstructorReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return ArrayObject::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (!$methodCall->class instanceof Name || strtolower($scope->resolveName($methodCall->class)) !== 'arrayobject') {
			return null;
		}
		$args = $methodCall->getArgs();
		if (!isset($args[0]) || !$scope->getType($args[0]->value)->isObject()->yes()) {
			return null;
		}

		// The object branch of array<TKey, TValue>|object does not infer the
		// templates, but its properties can still populate the ArrayObject.
		$mixed = new MixedType();
		return new GenericObjectType(ArrayObject::class, [$mixed->toArrayKey(), $mixed]);
	}

}
