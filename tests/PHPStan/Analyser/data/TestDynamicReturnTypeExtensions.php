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
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		$arg = $args[0]->value;
		if (!($arg instanceof \PhpParser\Node\Expr\ClassConstFetch)) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		if (!($arg->class instanceof \PhpParser\Node\Name)) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
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
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		$argType = $scope->getType($args[0]->value);
		if (!$argType instanceof ConstantStringType) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
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
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		$arg = $args[0]->value;
		if (!($arg instanceof \PhpParser\Node\Expr\ClassConstFetch)) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		if (!($arg->class instanceof \PhpParser\Node\Name)) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
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
		return ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();
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

class BackedEnumGetValueDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return \BackedEnum::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getValue';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): ?Type {
		return $methodReflection->getDeclaringClass()->getBackedEnumType();
	}
}

class Bug15303DynamicFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(\PHPStan\Reflection\FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['Bug15303\keyBy', 'Bug15303\groupBy'], true);
	}

	public function getTypeFromFunctionCall(\PHPStan\Reflection\FunctionReflection $functionReflection, \PhpParser\Node\Expr\FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		$valueType = $scope->getType($args[0]->value)->getIterableValueType();
		$callback = $args[1]->value;
		if ($callback instanceof \PhpParser\Node\Expr\Array_) {
			$lastItem = $callback->items[count($callback->items) - 1];
			$callback = $lastItem->value;
		}

		return Bug15303Helper::getKeyedArrayType($scope, $callback, $valueType);
	}

}

class Bug15303DynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \Bug15303\Collection::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'keyBy';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();

		return Bug15303Helper::getKeyedArrayType($scope, $args[0]->value, new ObjectType(\Bug15303\User::class));
	}

}

class Bug15303Helper
{

	public static function getKeyedArrayType(Scope $scope, \PhpParser\Node\Expr $callback, Type $valueType): ?Type
	{
		if (!$scope instanceof \PHPStan\Analyser\MutatingScope) {
			return null;
		}

		$pushed = $scope->pushInFunctionCall(null, new \PHPStan\Reflection\Php\DummyParameter('callback', new \PHPStan\Type\CallableType([
			new \PHPStan\Reflection\Native\NativeParameterReflection('param', false, $valueType, \PHPStan\Reflection\PassedByReference::createNo(), false, null),
		], new \PHPStan\Type\MixedType()), false, \PHPStan\Reflection\PassedByReference::createNo(), false, null), false);
		$closure = $pushed->getType($callback);
		if (!$closure instanceof \PHPStan\Type\ClosureType) {
			return null;
		}

		return new \PHPStan\Type\ArrayType($closure->getReturnType(), $valueType);
	}

}
