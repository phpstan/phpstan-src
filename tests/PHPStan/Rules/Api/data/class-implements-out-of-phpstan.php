<?php

namespace App\ClassImplements;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class Foo implements DynamicThrowTypeExtensionProvider
{
	public function getDynamicFunctionThrowTypeExtensions(): array
	{
		// TODO: Implement getDynamicFunctionThrowTypeExtensions() method.
	}

	public function getDynamicMethodThrowTypeExtensions(): array
	{
		// TODO: Implement getDynamicMethodThrowTypeExtensions() method.
	}

	public function getDynamicStaticMethodThrowTypeExtensions(): array
	{
		// TODO: Implement getDynamicStaticMethodThrowTypeExtensions() method.
	}

}

class Bar implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		// TODO: Implement isFunctionSupported() method.
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?\PHPStan\Type\Type
	{
		// TODO: Implement getThrowTypeFromFunctionCall() method.
	}

}

class Baz implements Type
{
	public function getReferencedClasses(): array
	{
		// TODO: Implement getReferencedClasses() method.
	}

	public function accepts(Type $type, bool $strictTypes): \PHPStan\TrinaryLogic
	{
		// TODO: Implement accepts() method.
	}

	public function isSuperTypeOf(Type $type): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isSuperTypeOf() method.
	}

	public function equals(Type $type): bool
	{
		// TODO: Implement equals() method.
	}

	public function describe(VerbosityLevel $level): string
	{
		// TODO: Implement describe() method.
	}

	public function canAccessProperties(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement canAccessProperties() method.
	}

	public function hasProperty(string $propertyName): \PHPStan\TrinaryLogic
	{
		// TODO: Implement hasProperty() method.
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\PropertyReflection
	{
		// TODO: Implement getProperty() method.
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
	{
		// TODO: Implement getUnresolvedPropertyPrototype() method.
	}

	public function canCallMethods(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement canCallMethods() method.
	}

	public function hasMethod(string $methodName): \PHPStan\TrinaryLogic
	{
		// TODO: Implement hasMethod() method.
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\ExtendedMethodReflection
	{
		// TODO: Implement getMethod() method.
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
	{
		// TODO: Implement getUnresolvedMethodPrototype() method.
	}

	public function canAccessConstants(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement canAccessConstants() method.
	}

	public function hasConstant(string $constantName): \PHPStan\TrinaryLogic
	{
		// TODO: Implement hasConstant() method.
	}

	public function getConstant(string $constantName): \PHPStan\Reflection\ConstantReflection
	{
		// TODO: Implement getConstant() method.
	}

	public function isIterable(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isIterable() method.
	}

	public function isIterableAtLeastOnce(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isIterableAtLeastOnce() method.
	}

	public function getIterableKeyType(): \PHPStan\Type\Type
	{
		// TODO: Implement getIterableKeyType() method.
	}

	public function getIterableValueType(): \PHPStan\Type\Type
	{
		// TODO: Implement getIterableValueType() method.
	}

	public function isArray(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isArray() method.
	}

	public function isOversizedArray(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isOversizedArray() method.
	}

	public function isList(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isList() method.
	}

	public function isOffsetAccessible(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isOffsetAccessible() method.
	}

	public function hasOffsetValueType(Type $offsetType): \PHPStan\TrinaryLogic
	{
		// TODO: Implement hasOffsetValueType() method.
	}

	public function getOffsetValueType(Type $offsetType): \PHPStan\Type\Type
	{
		// TODO: Implement getOffsetValueType() method.
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): \PHPStan\Type\Type
	{
		// TODO: Implement setOffsetValueType() method.
	}

	public function unsetOffset(Type $offsetType): Type
	{
		// TODO: Implement unsetOffset() method.
	}

	public function isCallable(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isCallable() method.
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		// TODO: Implement getCallableParametersAcceptors() method.
	}

	public function isCloneable(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isCloneable() method.
	}

	public function toBoolean(): \PHPStan\Type\BooleanType
	{
		// TODO: Implement toBoolean() method.
	}

	public function toNumber(): \PHPStan\Type\Type
	{
		// TODO: Implement toNumber() method.
	}

	public function toInteger(): \PHPStan\Type\Type
	{
		// TODO: Implement toInteger() method.
	}

	public function toFloat(): \PHPStan\Type\Type
	{
		// TODO: Implement toFloat() method.
	}

	public function toString(): \PHPStan\Type\Type
	{
		// TODO: Implement toString() method.
	}

	public function toArray(): \PHPStan\Type\Type
	{
		// TODO: Implement toArray() method.
	}

	public function toArrayKey(): \PHPStan\Type\Type
	{
		// TODO: Implement toArrayKey() method.
	}

	public function isSmallerThan(Type $otherType): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isSmallerThan() method.
	}

	public function isSmallerThanOrEqual(Type $otherType): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isSmallerThanOrEqual() method.
	}

	public function isString(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isString() method.
	}

	public function isNumericString(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isNumericString() method.
	}

	public function isNonEmptyString(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isNumericString() method.
	}

	public function isLiteralString(): \PHPStan\TrinaryLogic
	{
		// TODO: Implement isLiteralString() method.
	}

	public function getSmallerType(): \PHPStan\Type\Type
	{
		// TODO: Implement getSmallerType() method.
	}

	public function getSmallerOrEqualType(): \PHPStan\Type\Type
	{
		// TODO: Implement getSmallerOrEqualType() method.
	}

	public function getGreaterType(): \PHPStan\Type\Type
	{
		// TODO: Implement getGreaterType() method.
	}

	public function getGreaterOrEqualType(): \PHPStan\Type\Type
	{
		// TODO: Implement getGreaterOrEqualType() method.
	}

	public function inferTemplateTypes(Type $receivedType): \PHPStan\Type\Generic\TemplateTypeMap
	{
		// TODO: Implement inferTemplateTypes() method.
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		// TODO: Implement getReferencedTemplateTypes() method.
	}

	public function traverse(callable $cb): \PHPStan\Type\Type
	{
		// TODO: Implement traverse() method.
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		// TODO: Implement traverseSimultaneously() method.
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		// TODO: Implement generalize() method.
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		// TODO: Implement tryRemove() method.
	}

	public static function __set_state(array $properties): \PHPStan\Type\Type
	{
		// TODO: Implement __set_state() method.
	}


}

abstract class Dolor implements ReflectionProvider
{

}

abstract class MyScope implements Scope
{

}

abstract class MyFunctionReflection implements FunctionReflection
{}


abstract class MyMethodReflection implements ExtendedMethodReflection
{}
