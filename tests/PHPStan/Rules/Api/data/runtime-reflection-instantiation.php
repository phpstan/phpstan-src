<?php

namespace RuntimeReflectionInstantiation;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class Foo
{

	public function doFoo(object $o): void
	{
		new \stdClass();
		new \ReflectionMethod($o, 'foo');
	}

}

class Bar implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		// TODO: Implement getClass() method.
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		// TODO: Implement isMethodSupported() method.
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		// TODO: Implement getTypeFromMethodCall() method.
	}

	public function doFoo(object $o, \Fiber $f, \Generator $g): void
	{
		new \stdClass();
		new \ReflectionMethod($o, 'foo');
		new \ReflectionClass(\stdClass::class);
		new \ReflectionClassConstant(\stdClass::class, 'foo');


		new \ReflectionZendExtension('foo');
		new \ReflectionExtension('foo');
		new \ReflectionFunction('foo');
		new \ReflectionObject($o);
		new \ReflectionParameter('foo', 'foo');
		new \ReflectionProperty(\stdClass::class, 'foo');
		new \ReflectionGenerator($g);
		new \ReflectionFiber($f);
		new \ReflectionEnum(\stdClass::class);
		new \ReflectionEnumBackedCase(\stdClass::class, 'foo');
	}

}
