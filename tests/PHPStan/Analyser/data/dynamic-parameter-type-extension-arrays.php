<?php

namespace DynamicParameterTypeExtensionArrays;

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
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionParameterTypeExtension;
use PHPStan\Type\DynamicMethodParameterTypeExtension;
use PHPStan\Type\DynamicStaticMethodParameterTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\MixedType;
use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;

final class DynamicParameterTypeExtension implements DynamicFunctionParameterTypeExtension, DynamicMethodParameterTypeExtension, DynamicStaticMethodParameterTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return true;
	}

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return true;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return true;
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return $this->getType($methodReflection, $methodCall, $parameter, $scope);
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return $this->getType($methodReflection, $methodCall, $parameter, $scope);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return $this->getType($functionReflection, $functionCall, $parameter, $scope);
	}

	private function getType(
		FunctionReflection|MethodReflection $functionReflection,
		FuncCall|MethodCall|StaticCall $call,
		ParameterReflection $parameter,
		Scope $scope,
	): ?Type
	{
		$arg = $call->getArgs()[0] ?? null;
		if (!$arg) {
			return null;
		}

		$type = $scope->getType($arg->value)->getConstantArrays()[0] ?? null;
		if (!$type) {
			return null;
		}

		$replacements = [
			'a' => new IntegerType(),
			'b' => new StringType(),
			0 => new IntegerType(),
			1 => new StringType(),
			2 => new FloatType(),
		];

		foreach ($replacements as $key => $value) {
			$keyType = is_int($key) ? new ConstantIntegerType($key) : new ConstantStringType($key);
			if ($type->hasOffsetValueType($keyType)->no()) {
				continue;
			}

			$newType = new CallableType([
				new NativeParameterReflection('test', false, new GenericObjectType(Generic::class, [$value]), PassedByReference::createNo(), false, null),
			], new MixedType(), false);

			$type = $type->setOffsetValueType($keyType, $newType, false);
		}

		return $type;
	}
}

class Foo
{

	/** @param array<string, callable(Generic<array-key>)> $array */
	public function methodWithArray($array) {}

	public static function staticMethodWithArray(array $array) {}

}

/** @template T */
class Generic
{
	public function __construct(
		/** @var T */
		private mixed $value,
	) {
	}

	/** @return T */
	public function getValue()
	{
		return $this->value;
	}
}

/** @param array<string, callable(Generic<array-key>)> $array */
function functionWithArray(array $array): void {}

/** @param list<callable(Generic<array-key>)> $list */
function functionWithNumericArray(array $list): void {}

function test(Foo $foo): void
{
	functionWithArray([
		fn ($i) => assertType('int', $i->getValue()),
		fn ($i) => assertType('string', $i->getValue()),
		fn ($i) => assertType('float', $i->getValue()),
	]);

	functionWithArray([
		0 => fn ($i) => assertType('int', $i->getValue()),
		1 => fn ($i) => assertType('string', $i->getValue()),
		2 => fn ($i) => assertType('float', $i->getValue()),
	]);

	functionWithArray([
		'a' => fn ($i) => assertType('int', $i->getValue()),
		'b' => fn ($i) => assertType('string', $i->getValue()),
		'c' => fn (int $i) => assertType('int', $i),
	]);
	$foo->methodWithArray([
		'a' => fn ($i) => assertType('int', $i->getValue()),
		'b' => fn ($i) => assertType('string', $i->getValue()),
		'c' => fn (int $i) => assertType('int', $i),
	]);
	Foo::staticMethodWithArray([
		'a' => fn ($i) => assertType('int', $i->getValue()),
		'b' => fn ($i) => assertType('string', $i->getValue()),
		'c' => fn (int $i) => assertType('int', $i),
	]);

	functionWithArray([
		'a' => function ($i) { assertType('int', $i->getValue()); },
		'b' => function ($i) { assertType('string', $i->getValue()); },
		'c' => function (int $i) { assertType('int', $i); },
	]);
	$foo->methodWithArray([
		'a' => function ($i) { assertType('int', $i->getValue()); },
		'b' => function ($i) { assertType('string', $i->getValue()); },
		'c' => function (int $i) { assertType('int', $i); },
	]);
	Foo::staticMethodWithArray([
		'a' => function ($i) { assertType('int', $i->getValue()); },
		'b' => function ($i) { assertType('string', $i->getValue()); },
		'c' => function (int $i) { assertType('int', $i); },
	]);
}
