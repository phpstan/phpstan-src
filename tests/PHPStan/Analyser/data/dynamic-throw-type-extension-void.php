<?php

namespace DynamicThrowTypeExtensionVoid;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function PHPStan\Testing\assertVariableCertainty;

class FunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'DynamicThrowTypeExtensionVoid\throwOrNot';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (count($funcCall->getArgs()) < 1) {
			return $functionReflection->getThrowType();
		}

		$argType = $scope->getType($funcCall->getArgs()[0]->value);
		if ((new ConstantBooleanType(false))->isSuperTypeOf($argType)->yes()) {
			return new VoidType();
		}

		return $functionReflection->getThrowType();
	}

}

class MethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'throwOrNot';
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return $methodReflection->getThrowType();
		}

		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		if ((new ConstantBooleanType(false))->isSuperTypeOf($argType)->yes()) {
			return new VoidType();
		}

		return $methodReflection->getThrowType();
	}

}

class StaticMethodThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class
			&& in_array($methodReflection->getName(), ['staticThrowOrNot', '__construct'], true);
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return $methodReflection->getThrowType();
		}

		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		if ((new ConstantBooleanType(false))->isSuperTypeOf($argType)->yes()) {
			return new VoidType();
		}

		return $methodReflection->getThrowType();
	}

}

/** @throws \Exception */
function throwOrNot(bool $need): int
{
	if ($need) {
		throw new \Exception();
	}

	return 1;
}

class Foo
{

	/** @throws \Exception */
	public function __construct(bool $need)
	{
		if ($need) {
			throw new \Exception();
		}
	}

	/** @throws \Exception */
	public function throwOrNot(bool $need): int
	{
		if ($need) {
			throw new \Exception();
		}

		return 1;
	}

	/** @throws \Exception */
	public static function staticThrowOrNot(bool $need): int
	{
		if ($need) {
			throw new \Exception();
		}

		return 1;
	}

	public function doFunctionThrows(): void
	{
		try {
			$maybe = throwOrNot(true);
		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $maybe);
		}
	}

	public function doFunction(): void
	{
		try {
			$yes = throwOrNot(false);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $yes);
		}
	}

	public function doMethod(): void
	{
		try {
			$yes = $this->throwOrNot(false);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $yes);
		}
	}

	public function doStaticMethod(): void
	{
		try {
			$yes = self::staticThrowOrNot(false);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $yes);
		}
	}

	public function doNew(): void
	{
		try {
			$yes = new self(false);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $yes);
		}
	}

}
