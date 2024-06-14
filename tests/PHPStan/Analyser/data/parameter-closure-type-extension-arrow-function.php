<?php // onlyif PHP_VERSION_ID >= 70400

namespace ParameterClosureTypeExtensionArrowFunction;

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
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\MixedType;
use function PHPStan\Testing\assertType;

class FunctionParameterClosureTypeExtension implements \PHPStan\Type\FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'ParameterClosureTypeExtensionArrowFunction\functionWithCallable';
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

class MethodParameterClosureTypeExtension implements \PHPStan\Type\MethodParameterClosureTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class &&
			$parameter->getName() === 'callback' &&
			$methodReflection->getName() === 'methodWithCallable';
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

class StaticMethodParameterClosureTypeExtension implements \PHPStan\Type\StaticMethodParameterClosureTypeExtension
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
		return new CallableType(
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
	 * @param callable(Generic<array-key>) $callback
	 *
	 * @return void
	 */
	public function methodWithCallable(int $foo, callable $callback)
	{

	}

	/**
	 * @return void
	 */
	public static function staticMethodWithCallable(callable $callback)
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

	$foo->methodWithCallable(1, fn ($i) => assertType('int', $i->getValue()));

	(new Foo)->methodWithCallable(2, fn (Generic $i) => assertType('string', $i->getValue()));

	Foo::staticMethodWithCallable(fn ($i) => assertType('float', $i));
}

functionWithCallable(1, fn ($i) => assertType('int', $i->getValue()));

functionWithCallable(2, fn (Generic $i) => assertType('string', $i->getValue()));
