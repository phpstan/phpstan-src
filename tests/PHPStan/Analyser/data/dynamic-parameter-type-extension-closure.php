<?php

namespace DynamicParameterTypeExtensionClosure;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\MixedType;
use function PHPStan\Testing\assertType;

class FunctionDynamicParameterTypeExtension implements \PHPStan\Type\FunctionDynamicParameterTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'DynamicParameterTypeExtensionClosure\functionWithCallable';
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
					new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new IntegerType()]), PassedByReference::createNo(), false, null),
				],
				new MixedType(),
			);
		}

		return new ClosureType(
			[
				new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [new StringType()]), PassedByReference::createNo(), false, null),
			],
			new MixedType(),
		);
	}
}

class MethodDynamicParameterTypeExtension implements \PHPStan\Type\MethodDynamicParameterTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class &&
			$parameter->getName() === 'relations' &&
			$methodReflection->getName() === 'with';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		ParameterReflection $parameter,
		Scope $scope
	): ?Type {
		return new ConstantArrayType([
			new ConstantStringType('user'),
		], [
			new ClosureType(
				[
					new NativeParameterReflection(
						'callback',
						false,
						new GenericObjectType('Illuminate\Database\Eloquent\Builder', [
							new ObjectType('Illuminate\Database\Eloquent\Model'),
						]),
						PassedByReference::createNo(),
						false,
						null,
					),
				],
				new MixedType(),
			),
		]);
	}
}

class StaticMethodDynamicParameterTypeExtension implements \PHPStan\Type\StaticMethodDynamicParameterTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticMethodWithCallable';
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
			new MixedType()
		);
	}
}

class Foo
{

	/**
	 * @param int $foo
	 * @param mixed $bar
	 *
	 * @return void
	 */
	public function methodWithCallable(int $foo, mixed $bar)
	{

	}

	/**
	 * @return void
	 */
	public static function staticMethodWithCallable(callable $callback)
	{

	}

	public function with(array $relations)
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
 * @param callable(Generic<array-key>) $callback
 *
 * @return void
 */
function functionWithCallable(int $foo, callable $callback)
{

}

function test(Foo $foo): void
{

	(new Foo)->with([
		'users' => function ($arg) {
			assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $arg);
		},
	]);

	Foo::staticMethodWithCallable(function ($i) {
		assertType('float', $i);
	});

}

functionWithCallable(1, function ($i) {
	assertType('int', $i->getValue());
});
functionWithCallable(2, function (Generic $i) {
	assertType('string', $i->getValue());
});
