<?php

namespace DynamicMethodThrowTypeExtension;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;
use function PHPStan\Testing\assertVariableCertainty;

class MethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'throwOrNot';
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) < 1) {
			return $methodReflection->getThrowType();
		}

		$argType = $scope->getType($methodCall->args[0]->value);
		if ((new ConstantBooleanType(false))->isSuperTypeOf($argType)->yes()) {
			return null;
		}

		return $methodReflection->getThrowType();
	}

}

class StaticMethodThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticThrowOrNot';
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) < 1) {
			return $methodReflection->getThrowType();
		}

		$argType = $scope->getType($methodCall->args[0]->value);
		if ((new ConstantBooleanType(false))->isSuperTypeOf($argType)->yes()) {
			return null;
		}

		return $methodReflection->getThrowType();
	}

}

class Foo
{

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

	public function doFoo1()
	{
		try {
			$result = $this->throwOrNot(true);
		} catch (\Exception) {

		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
		}
	}

	public function doFoo2()
	{
		try {
			$result = $this->throwOrNot(false);
		} catch (\Exception) { // DeadCatch

		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

	public function doFoo3()
	{
		try {
			$result = self::staticThrowOrNot(true);
		} catch (\Exception) {

		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
		}
	}

	public function doFoo4()
	{
		try {
			$result = self::staticThrowOrNot(false);
		} catch (\Exception) { // DeadCatch

		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

	/** @param true $value */
	public function doFooPhpdoc1(bool $value)
	{
		try {
			$result = $this->throwOrNot($value);
		} catch (\Exception) {

		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
		}
	}

	/** @param false $value */
	public function doFooPhpdoc2(bool $value)
	{
		try {
			$result = $this->throwOrNot($value);
		} catch (\Exception) { // DeadCatch if phpdoc is trusted

		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

	/** @param true $value */
	public function doFooPhpdoc3(bool $value)
	{
		try {
			$result = self::staticThrowOrNot($value);
		} catch (\Exception) {

		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $result);
		}
	}

	/** @param false $value */
	public function doFooPhpdoc4(bool $value)
	{
		try {
			$result = self::staticThrowOrNot($value);
		} catch (\Exception) { // DeadCatch if phpdoc is trusted

		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $result);
		}
	}

}
