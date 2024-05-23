<?php

namespace ClosureTypeChangingExtension;

use Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function PHPStan\Testing\assertType;

class FunctionClosureTypeChangingExtension implements \PHPStan\Type\FunctionClosureTypeChangingExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ClosureTypeChangingExtension\functionWithClosure';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): ?Type {
		$args = $functionCall->getArgs();

		if (count($args) < 2) {
			return null;
		}

		$integer = $scope->getType($args[0]->value)->getConstantScalarValues()[0];

		if ($integer === 1) {
			return new ClosureType(
				[
					new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new IntegerType()]), PassedByReference::createNo(), false, null),
				],
				new VoidType()
			);
		}

		return new ClosureType(
			[
				new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new StringType()]), PassedByReference::createNo(), false, null),
			],
			new VoidType()
		);
	}
}

class MethodClosureTypeChangingExtension implements \PHPStan\Type\MethodClosureTypeChangingExtension
{


	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'methodWithClosure';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): ?Type {
		$args = $methodCall->getArgs();

		if (count($args) < 2) {
			return null;
		}

		$integer = $scope->getType($args[0]->value)->getConstantScalarValues()[0];

		if ($integer === 1) {
			return new ClosureType(
				[
					new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new IntegerType()]), PassedByReference::createNo(), false, null),
				],
				new VoidType()
			);
		}

		return new ClosureType(
			[
				new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new StringType()]), PassedByReference::createNo(), false, null),
			],
			new VoidType()
		);
	}
}

class StaticMethodClosureTypeChangingExtension implements \PHPStan\Type\StaticMethodClosureTypeChangingExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticMethodWithClosure';
	}

	public function getTypeFromStaticMethodCall(
		MethodReflection $methodReflection,
		StaticCall $methodCall,
		Scope $scope
	): ?Type {
		return new ClosureType(
			[
				new NativeParameterReflection('test', false, new FloatType(), PassedByReference::createNo(), false, null),
			],
			new VoidType()
		);
	}
}

class Foo
{

	/**
	 * @param int $foo
	 * @param Closure(Generic<array-key>): void $callback
	 *
	 * @return void
	 */
	public function methodWithClosure(int $foo, Closure $callback)
	{

	}

	/**
	 * @param Closure(): void $callback
	 *
	 * @return void
	 */
	public static function staticMethodWithClosure(Closure $callback)
	{

	}

}

/**
 * @template T
 */
class Generic
{
	private mixed $value;

	/**
	 * @param T $value
	 */
	public function __construct(mixed $value)
	{
		$this->value = $value;
	}

	/**
	 * @return T
	 */
	public function getValue()
	{
		return $this->value;
	}
}

/**
 * @param int $foo
 * @param Closure(Generic<array-key>): void $callback
 *
 * @return void
 */
function functionWithClosure(int $foo, Closure $callback)
{

}

/**
 * @param class-string<Foo> $fooString
 */
function test(Foo $foo, string $fooString): void
{
	$foo->methodWithClosure(1, function ($i) {
		assertType('int', $i->getValue());
	});

	(new Foo)->methodWithClosure(2, function (Generic $i) {
		assertType('string', $i->getValue());
	});

	Foo::staticMethodWithClosure(function ($i) {
		assertType('float', $i);
	});

	$fooString::staticMethodWithClosure(function ($i) {
		assertType('float', $i);
	});
}

functionWithClosure(1, function ($i) {
	assertType('int', $i->getValue());
});

functionWithClosure(2, function (Generic $i) {
	assertType('string', $i->getValue());
});
