<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
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

/**
 * Modify return types by reresolving static/$this type with virtual interfaces removed.
 *
 * Used in atk4/data repo.
 */
class Bug7344DynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return \Bug7344\Model::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return true;
	}

	protected function unresolveMethodReflection(ResolvedMethodReflection $methodReflection): MethodReflection
	{
		$methodReflection = \Closure::bind(function () use ($methodReflection) { return $methodReflection->reflection; }, null, ResolvedMethodReflection::class)();
		if (!$methodReflection instanceof ChangedTypeMethodReflection) {
			throw new \Exception('Unexpected method reflection class: ' . get_class($methodReflection));
		}

		$methodReflection = \Closure::bind(function () use ($methodReflection) { return $methodReflection->reflection; }, null, ChangedTypeMethodReflection::class)();

		return $methodReflection;
	}

	protected function resolveMethodReflection(MethodReflection $methodReflection, Type $calledOnType): MethodReflection
	{
		$resolver = (new CalledOnTypeUnresolvedMethodPrototypeReflection(
			$methodReflection,
			$methodReflection->getDeclaringClass(),
			false,
			$calledOnType
		));

		return $resolver->getTransformedMethod();
	}

	protected function reresolveMethodReflection(MethodReflection $methodReflection, Type $calledOnType): MethodReflection
	{
		if ($methodReflection instanceof UnionTypeMethodReflection) {
			$methodReflection = new UnionTypeMethodReflection(
				$methodReflection->getName(),
				array_map(
					function ($v) use ($calledOnType) { return $this->reresolveMethodReflection($v, $calledOnType); },
					\Closure::bind(function () use ($methodReflection) { return $methodReflection->methods; }, null, UnionTypeMethodReflection::class)()
				)
			);
		} else {
			$methodReflection = $this->unresolveMethodReflection($methodReflection);
			$methodReflection = $this->resolveMethodReflection($methodReflection, $calledOnType);
		}

		return $methodReflection;
	}

	protected function removeVirtualInterfacesFromType(Type $type): Type
	{
		if ($type instanceof IntersectionType) {
			$types = [];
			foreach ($type->getTypes() as $t) {
				$t = $this->removeVirtualInterfacesFromType($t);
				if (!$t instanceof NeverType) {
					$types[] = $t;
				}
			}

			return count($types) === 0 ? new NeverType() : TypeCombinator::intersect(...$types);
		}

		if ($type instanceof ObjectType && $type->isInstanceOf(\Bug7344\PhpdocTypeInterface::class)->yes()) {
			return new NeverType();
		}

		return $type->traverse(\Closure::fromCallable([$this, 'removeVirtualInterfacesFromType']));
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type {
		// resolve static type and remove all virtual interfaces from it
		if ($methodCall instanceof StaticCall) {
			$classNameType = $scope->getType(new ClassConstFetch($methodCall->class, 'class'));
			$calledOnOrigType = new ObjectType($classNameType->getValue());
		} else {
			$calledOnOrigType = $scope->getType($methodCall->var);
		}
		$calledOnType = $this->removeVirtualInterfacesFromType($calledOnOrigType);

		$methodReflectionReresolved = $this->reresolveMethodReflection($methodReflection, $calledOnType);

		return ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflectionReresolved->getVariants()
		)->getReturnType();
	}
}
