<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;

/**
 * The types whose classes a call or a declaration depends on, for the handlers creating Dependencies.
 */
#[ReferencedByTurboExtension(key: 'dependencyTypes')]
final class DependencyTypes
{

	/**
	 * What calling one of the variants can make of its arguments - @param-out and @param-closure-this.
	 *
	 * @param array<ParametersAcceptor> $variants
	 * @return list<Type|null>
	 */
	public static function ofCalledVariants(array $variants): array
	{
		$types = [];
		foreach ($variants as $variant) {
			foreach (self::ofCalledParameters($variant->getParameters()) as $type) {
				$types[] = $type;
			}
		}

		return $types;
	}

	/**
	 * @param array<ParameterReflection> $parameters
	 * @return list<Type|null>
	 */
	public static function ofCalledParameters(array $parameters): array
	{
		$types = [];
		foreach ($parameters as $parameter) {
			if (!$parameter instanceof ExtendedParameterReflection) {
				continue;
			}

			$types[] = $parameter->getOutType();
			$types[] = $parameter->getClosureThisType();
		}

		return $types;
	}

	/**
	 * What calling the method can make of its arguments and of the object it is called on.
	 *
	 * @return list<Type|null>
	 */
	public static function ofCalledMethod(ExtendedMethodReflection $methodReflection, bool $withAssertsAndSelfOut): array
	{
		$types = self::ofCalledVariants($methodReflection->getVariants());
		if (!$withAssertsAndSelfOut) {
			return $types;
		}

		foreach (self::ofAsserts($methodReflection->getAsserts()) as $type) {
			$types[] = $type;
		}
		$types[] = $methodReflection->getSelfOutType();

		return $types;
	}

	/**
	 * @return list<Type>
	 */
	public static function ofAsserts(Assertions $asserts): array
	{
		$types = [];
		foreach ($asserts->getAll() as $assertTag) {
			$types[] = $assertTag->getType();
			$types[] = $assertTag->getOriginalType();
		}

		return $types;
	}

	/**
	 * The classes a string naming a class points at - `new $class()`, `$x instanceof $class` - so that
	 * they are depended on the way a written-out class name is.
	 *
	 * @return list<string>
	 */
	public static function classNamesOfClassString(Type $type): array
	{
		$classNames = [];
		foreach ($type->getConstantStrings() as $constantString) {
			foreach ($constantString->getClassStringObjectType()->getObjectClassNames() as $className) {
				$classNames[] = $className;
			}
		}

		return $classNames;
	}

	/**
	 * The signature of a closure or an arrow function, from its type.
	 *
	 * @return list<Type>
	 */
	public static function ofClosureType(Type $closureType): array
	{
		if (!$closureType instanceof ClosureType) {
			return [];
		}

		$types = [];
		foreach ($closureType->getParameters() as $parameter) {
			$types[] = $parameter->getType();
		}
		$types[] = $closureType->getReturnType();

		return $types;
	}

	/**
	 * The signature of a declared function, method or property hook.
	 *
	 * @return list<Type|null>
	 */
	public static function ofDeclaration(PhpFunctionFromParserNodeReflection $reflection, bool $withAsserts): array
	{
		$types = [$reflection->getThrowType()];
		foreach ($reflection->getParameters() as $parameter) {
			$types[] = $parameter->getNativeType();
			$types[] = $parameter->getPhpDocType();
			$types[] = $parameter->getOutType();
			$types[] = $parameter->getClosureThisType();
		}
		$types[] = $reflection->getNativeReturnType();
		$types[] = $reflection->getPhpDocReturnType();

		if (!$withAsserts) {
			return $types;
		}

		foreach (self::ofAsserts($reflection->getAsserts()) as $type) {
			$types[] = $type;
		}

		return $types;
	}

}
