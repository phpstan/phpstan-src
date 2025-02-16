<?php

namespace DynamicParameterTypeExtensionClosuresErrors;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\CallableType;
use PHPStan\Type\DynamicMethodParameterTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

/** @template T */
class Generic
{
	/** @param T $value */
	public function __construct(private mixed $value) {}

	/** @return T */
	public function getValue() { return $this->value; }
}

class Foo
{
	public function methodWithCallable(int $foo, callable $callback): void {}
}

final class ErrorTestExtension implements DynamicMethodParameterTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class
			&& $parameter->getName() === 'callback'
			&& $methodReflection->getName() === 'methodWithCallable';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$integer = $scope->getType($args[0]->value)->getConstantScalarValues()[0] ?? null;

		$valueType = $integer === 1 ? new IntegerType() : new StringType();

		return new CallableType(
			[
				new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [$valueType]), PassedByReference::createNo(), false, null),
			],
			new MixedType(),
		);
	}

}

function acceptInt(int $value): void {}
function acceptString(string $value): void {}

function testErrorCases(Foo $foo): void
{
	// Extension overrides param to Generic<int>, getValue() returns int
	// Passing int where string is expected should be an error
	$foo->methodWithCallable(1, function ($i) {
		acceptString($i->getValue());
	});

	// Extension overrides param to Generic<string>, getValue() returns string
	// Passing string where int is expected should be an error
	$foo->methodWithCallable(2, function ($i) {
		acceptInt($i->getValue());
	});

	// Calling non-existent method on overridden type should be an error
	$foo->methodWithCallable(1, function ($i) {
		$i->nonExistentMethod();
	});

	// No error: correct usage matches overridden parameter type
	$foo->methodWithCallable(1, function ($i) {
		acceptInt($i->getValue());
	});

	// No error: correct usage for string variant
	$foo->methodWithCallable(2, function ($i) {
		acceptString($i->getValue());
	});
}
