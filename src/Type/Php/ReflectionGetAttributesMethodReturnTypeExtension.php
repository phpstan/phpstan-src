<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionAttribute;
use function count;

final class ReflectionGetAttributesMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/**
	 * @param class-string $className One of reflection classes: https://www.php.net/manual/en/book.reflection.php
	 */
	public function __construct(private string $className)
	{
	}

	public function getClass(): string
	{
		return $this->className;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getAttributes';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return null;
		}
		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		$classType = $argType->getClassStringObjectType();

		$valueType = $this->resolveReflectionAttributeType($methodReflection, $classType);

		return new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $valueType), new AccessoryArrayListType()]);
	}

	private function resolveReflectionAttributeType(MethodReflection $methodReflection, Type $classType): Type
	{
		$returnType = $methodReflection->getVariants()[0]->getReturnType();
		$nativeReflectionAttributeType = new ObjectType(ReflectionAttribute::class);

		$valueTypes = [];
		foreach ($returnType->getIterableValueType()->getObjectClassNames() as $className) {
			if ($nativeReflectionAttributeType->isSuperTypeOf(new ObjectType($className))->yes()) {
				$valueTypes[] = new GenericObjectType($className, [$classType]);
			}
		}

		if (count($valueTypes) === 0) {
			return new GenericObjectType(ReflectionAttribute::class, [$classType]);
		}

		if (count($valueTypes) === 1) {
			return $valueTypes[0];
		}

		return TypeCombinator::union(...$valueTypes);
	}

}
