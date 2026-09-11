<?php // lint >= 8.0

namespace DynamicParameterTypeExtensionClosures;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\CallableType;
use PHPStan\Type\DynamicFunctionParameterTypeExtension;
use PHPStan\Type\DynamicMethodParameterTypeExtension;
use PHPStan\Type\DynamicStaticMethodParameterTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\MixedType;
use function PHPStan\Testing\assertType;

final class DynamicParameterTypeExtension implements DynamicFunctionParameterTypeExtension, DynamicMethodParameterTypeExtension, DynamicStaticMethodParameterTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'DynamicParameterTypeExtensionClosures\functionWithCallable';
	}

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class &&
			$parameter->getName() === 'callback' &&
			$methodReflection->getName() === 'methodWithCallable';
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		if ($methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticMethodWithCallable') {
			return true;
		}

		if ($methodReflection->getDeclaringClass()->getName() === Bar::class && $methodReflection->getName() === '__construct') {
			return true;
		}

		return false;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return $this->getType($functionReflection, $functionCall, $parameter, $scope);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return $this->getType($methodReflection, $methodCall, $parameter, $scope);
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		if ($methodReflection->getDeclaringClass()->getName() === Bar::class && $methodReflection->getName() === '__construct') {
			$args = $methodCall->getArgs();

			if (count($args) < 2) {
				return null;
			}

			$integer = $scope->getType($args[0]->value)->getConstantScalarValues()[0];

			if ($integer === 1) {
				return new CallableType(
					[
						new NativeParameterReflection('test', false, new IntegerType(), PassedByReference::createNo(), false, null),
					],
					new MixedType()
				);
			}

			return new CallableType(
				[
					new NativeParameterReflection('test', false, new StringType(), PassedByReference::createNo(), false, null),
				],
				new MixedType()
			);
		}

		return new CallableType(
			[
				new NativeParameterReflection('test', false, new FloatType(), PassedByReference::createNo(), false, null),
			],
			new MixedType()
		);
	}

	private function getType(
		FunctionReflection|MethodReflection $methodReflection,
		FuncCall|MethodCall $methodCall,
		ParameterReflection $parameter,
		Scope $scope,
	): ?Type {
		$args = $methodCall->getArgs();

		if (count($args) < 2) {
			return null;
		}

		$integer = $scope->getType($args[0]->value)->getConstantScalarValues()[0];

		if ($integer === 1) {
			return new CallableType(
				[
					new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new IntegerType()]), PassedByReference::createNo(), false, null),
				],
				new MixedType()
			);
		}

		return new CallableType(
			[
				new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new StringType()]), PassedByReference::createNo(), false, null),
			],
			new MixedType()
		);
	}
}

class Foo
{

	/**
	 * @param int $foo
	 * @param callable(Generic<array-key>) $callback
	 *
	 * @return void
	 */
	public function methodWithCallable(int $foo, callable $callback) {}

	/** @return void */
	public static function staticMethodWithCallable(callable $callback) {}

}

/** @template T */
class Generic
{
	private $value;

	/** @param T $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/** @return T */
	public function getValue()
	{
		return $this->value;
	}
}

class Bar
{

	/**
	 * @param int $foo
	 * @param callable(mixed) $callback
	 */
	public function __construct(int $foo, callable $callback)
	{

	}

}

/**
 * @param int $foo
 * @param callable(Generic<array-key>) $callback
 *
 * @return void
 */
function functionWithCallable(int $foo, callable $callback) {}

function test(Foo $foo): void
{

	// arrow functions
	$foo->methodWithCallable(1, fn ($i) => assertType('int', $i->getValue()));
	(new Foo)->methodWithCallable(2, fn (Generic $i) => assertType('string', $i->getValue()));
	Foo::staticMethodWithCallable(fn ($i) => assertType('float', $i));
	functionWithCallable(1, fn ($i) => assertType('int', $i->getValue()));
	functionWithCallable(2, fn (Generic $i) => assertType('string', $i->getValue()));

	new Bar(1, fn ($i) => assertType('int', $i));
	new Bar(2, fn ($i) => assertType('string', $i));


	// closures
	$foo->methodWithCallable(1, function ($i) { assertType('int', $i->getValue()); });
	(new Foo)->methodWithCallable(2, function (Generic $i) { assertType('string', $i->getValue()); });
	Foo::staticMethodWithCallable(function ($i) { assertType('float', $i); });
	functionWithCallable(1, function ($i) { assertType('int', $i->getValue()); });
	functionWithCallable(2, function (Generic $i) { assertType('string', $i->getValue()); });

	new Bar(1, function ($i) { assertType('int', $i); });
	new Bar(2, function ($i) { assertType('string', $i); });
}

/**
 * @param callable(int): void|null $callback
 */
function functionWithUnionCallable(callable|null $callback): void {}

/**
 * @param callable(int): string $callback
 */
function functionWithCallableReturnType(callable $callback): void {}

function testUnionCallable(): void
{
	// Test with union type containing callable and non-callable
	functionWithUnionCallable(fn ($i) => assertType('int', $i));
	functionWithUnionCallable(function ($i) { assertType('int', $i); });

	// Test closure return type checking
	functionWithCallableReturnType(fn ($i): string => 'test');
	functionWithCallableReturnType(function ($i): string { return 'test'; });
}

function testComplexExpressions(Foo $foo): void
{
	// Type narrowing inside overridden closure
	$foo->methodWithCallable(1, function ($i) {
		$val = $i->getValue();
		assertType('int', $val);

		if ($val > 0) {
			assertType('int<1, max>', $val);
		}
	});

	// Variable assignment and reuse
	functionWithCallable(2, function ($i) {
		$val = $i->getValue();
		assertType('string', $val);

		$upper = strtoupper($val);
		assertType('uppercase-string', $upper);
	});

	// Nested method calls on overridden type
	$foo->methodWithCallable(1, function ($i) {
		assertType('DynamicParameterTypeExtensionClosures\Generic<int>', $i);
		assertType('int', $i->getValue());
	});

	// Multiple statements in closure body
	functionWithCallable(1, function ($i) {
		$a = $i->getValue();
		$b = $i->getValue();
		assertType('int', $a);
		assertType('int', $b);
		assertType('int', $a + $b);
	});
}
