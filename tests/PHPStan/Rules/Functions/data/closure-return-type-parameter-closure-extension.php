<?php declare(strict_types = 1);

namespace ClosureReturnTypeParameterClosureExtension;

use Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Overrides the callback parameter with an array shape whose `k` offset is
 * nullable. The declared callback signature is `Closure(array{k: string}): string`,
 * so without the override the `$x['k'] !== null` branch would look always-true
 * and the closure's inferred return type would collapse to a single constant.
 */
function nullableOffsetClosureType(ParameterReflection $parameter): ClosureType
{
	$builder = ConstantArrayTypeBuilder::createEmpty();
	$builder->setOffsetValueType(new ConstantStringType('k'), TypeCombinator::union(new StringType(), new NullType()));

	return new ClosureType(
		[
			new NativeParameterReflection($parameter->getName(), $parameter->isOptional(), $builder->getArray(), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue()),
		],
		new StringType(),
	);
}

class FunctionParameterClosureTypeExtension implements \PHPStan\Type\FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'ClosureReturnTypeParameterClosureExtension\functionWithClosure' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return nullableOffsetClosureType($parameter);
	}

}

class MethodParameterClosureTypeExtension implements \PHPStan\Type\MethodParameterClosureTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'methodWithClosure' && $parameter->getName() === 'callback';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return nullableOffsetClosureType($parameter);
	}

}

class StaticMethodParameterClosureTypeExtension implements \PHPStan\Type\StaticMethodParameterClosureTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticMethodWithClosure' && $parameter->getName() === 'callback';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return nullableOffsetClosureType($parameter);
	}

}

class Foo
{

	/** @param Closure(array{k: string}): string $callback */
	public function methodWithClosure(Closure $callback): void
	{
	}

	/** @param Closure(array{k: string}): string $callback */
	public static function staticMethodWithClosure(Closure $callback): void
	{
	}

}

/** @param Closure(array{k: string}): string $callback */
function functionWithClosure(Closure $callback): void
{
}

function test(Foo $foo): void
{
	functionWithClosure(function (array $x): string {
		if ($x['k'] !== null) {
			return 'a';
		}
		return 'b';
	});

	$foo->methodWithClosure(function (array $x): string {
		if ($x['k'] !== null) {
			return 'a';
		}
		return 'b';
	});

	Foo::staticMethodWithClosure(function (array $x): string {
		if ($x['k'] !== null) {
			return 'a';
		}
		return 'b';
	});

	functionWithClosure(fn (array $x): string => $x['k'] !== null ? 'a' : 'b');
}
