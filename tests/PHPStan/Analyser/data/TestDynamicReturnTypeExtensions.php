<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnionTypeMethodReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class GetByPrimaryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \DynamicMethodReturnTypesNamespace\EntityManager::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), ['getByPrimary'], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): \PHPStan\Type\Type
	{
		$args = $methodCall->args;
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$arg = $args[0]->value;
		if (!($arg instanceof \PhpParser\Node\Expr\ClassConstFetch)) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		if (!($arg->class instanceof \PhpParser\Node\Name)) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		return new ObjectType((string) $arg->class);
	}

}

class OffsetGetDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \DynamicMethodReturnTypesNamespace\ComponentContainer::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'offsetGet';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$args = $methodCall->args;
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($args[0]->value);
		if (!$argType instanceof ConstantStringType) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		return new ObjectType($argType->getValue());
	}

}

class CreateManagerForEntityDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \DynamicMethodReturnTypesNamespace\EntityManager::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), ['createManagerForEntity'], true);
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): \PHPStan\Type\Type
	{
		$args = $methodCall->args;
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$arg = $args[0]->value;
		if (!($arg instanceof \PhpParser\Node\Expr\ClassConstFetch)) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		if (!($arg->class instanceof \PhpParser\Node\Name)) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		return new ObjectType((string) $arg->class);
	}

}

class ConstructDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \DynamicMethodReturnTypesNamespace\Foo::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): \PHPStan\Type\Type
	{
		return new ObjectWithoutClassType();
	}

}

class ConstructWithoutConstructor implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \DynamicMethodReturnTypesNamespace\FooWithoutConstructor::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): \PHPStan\Type\Type
	{
		return new ObjectWithoutClassType();
	}

}

class GetSelfDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension {

	public function getClass(): string
	{
		return \DynamicMethodReturnCompoundTypes\Collection::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getSelf';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		return new ObjectType(\DynamicMethodReturnCompoundTypes\Collection::class);
	}

}

class FooGetSelf implements DynamicMethodReturnTypeExtension {

	public function getClass(): string
	{
		return \DynamicMethodReturnCompoundTypes\Foo::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getSelf';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		return new ObjectType(\DynamicMethodReturnCompoundTypes\Foo::class);
	}

}


class ConditionalGetSingle implements DynamicMethodReturnTypeExtension {

	public function getClass(): string
	{
		return \DynamicMethodReturnGetSingleConditional\Foo::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'get';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
	}

}

class Bug7344DynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return \Bug7344\Model::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getModel';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type {
		return new IntegerType();
	}

}

class Bug7391BDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return \Bug7391B\Foo::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'm';
	}

	public function getTypeFromStaticMethodCall(
		MethodReflection $methodReflection,
		StaticCall $methodCall,
		Scope $scope
	): Type {
		// return instantiated type from class string
		return $scope->getType(new New_($methodCall->class));
	}
}


class ObjectHookedDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension {

	public function getClass(): string
	{
		return 'object';
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'methodReturningBoolNoMatterTheCaller';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
	{
		return new BooleanType();
	}

}
