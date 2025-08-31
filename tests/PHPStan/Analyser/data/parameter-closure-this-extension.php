<?php

namespace ParameterClosureThisExtension;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function PHPStan\Testing\assertType;

class TestFunctionParameterClosureThisExtension implements \PHPStan\Type\FunctionParameterClosureThisExtension
{
	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'ParameterClosureThisExtension\testFunction'
			&& $parameter->getName() === 'closure';
	}

	public function getClosureThisTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return new ObjectType(TestContext::class);
	}
}

class TestMethodParameterClosureThisExtension implements \PHPStan\Type\MethodParameterClosureThisExtension
{
	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === TestClass::class
			&& $methodReflection->getName() === 'methodWithClosure'
			&& $parameter->getName() === 'closure';
	}

	public function getClosureThisTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return new ObjectType(TestContext::class);
	}
}

class TestStaticMethodParameterClosureThisExtension implements \PHPStan\Type\StaticMethodParameterClosureThisExtension
{
	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === TestClass::class
			&& $methodReflection->getName() === 'staticMethodWithClosure'
			&& $parameter->getName() === 'closure';
	}

	public function getClosureThisTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return new ObjectType(TestContext::class);
	}
}

class TestContext
{
	public function contextMethod(): string
	{
		return 'context';
	}
}

class TestClass
{
	public function methodWithClosure(callable $closure): void
	{
	}

	public static function staticMethodWithClosure(callable $closure): void
	{
	}
}

/**
 * @param callable $closure
 */
function testFunction(callable $closure): void
{
}

testFunction(function () {
	assertType('ParameterClosureThisExtension\TestContext', $this);
	assertType('string', $this->contextMethod());
});

$test = new TestClass();
$test->methodWithClosure(function () {
	assertType('ParameterClosureThisExtension\TestContext', $this);
	assertType('string', $this->contextMethod());
});

TestClass::staticMethodWithClosure(function () {
	assertType('ParameterClosureThisExtension\TestContext', $this);
	assertType('string', $this->contextMethod());
});

testFunction(fn () => assertType('ParameterClosureThisExtension\TestContext', $this));

testFunction(static function () {
	assertType('*ERROR*', $this);
});

testFunction(static fn () => assertType('*ERROR*', $this));
