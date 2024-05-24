<?php

namespace ParameterClosureTypeExtension;

use Closure;
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
use PHPStan\Type\ClosureType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function PHPStan\Testing\assertType;

class FunctionParameterClosureTypeExtension implements \PHPStan\Type\FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'ParameterClosureTypeExtension\functionWithClosure';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		ParameterReflection $parameter,
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
					new NativeParameterReflection($parameter->getName(), $parameter->isOptional(), new GenericObjectType(Generic::class, [new IntegerType()]), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue()),
				],
				new VoidType()
			);
		}

		return new ClosureType(
			[
				new NativeParameterReflection($parameter->getName(), $parameter->isOptional(), new GenericObjectType(Generic::class, [new StringType()]), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue()),
			],
			new VoidType()
		);
	}
}

class MethodParameterClosureTypeExtension implements \PHPStan\Type\MethodParameterClosureTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class &&
			$parameter->getName() === 'callback' &&
			$methodReflection->getName() === 'methodWithClosure';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		ParameterReflection $parameter,
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
		} elseif ($integer === 5) {
			return new CallableType(
				[
					new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new IntegerType()]), PassedByReference::createNo(), false, null),
				],
				new MixedType()
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

class StaticMethodParameterClosureTypeExtension implements \PHPStan\Type\StaticMethodParameterClosureTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticMethodWithClosure';
	}

	public function getTypeFromStaticMethodCall(
		MethodReflection $methodReflection,
		StaticCall $methodCall,
		ParameterReflection $parameter,
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
	private $value;

	/**
	 * @param T $value
	 */
	public function __construct($value)
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

function test(Foo $foo): void
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
}

functionWithClosure(1, function ($i) {
	assertType('int', $i->getValue());
});

functionWithClosure(2, function (Generic $i) {
	assertType('string', $i->getValue());
});
