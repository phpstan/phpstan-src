<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * Answers questions about visibility and access rights for class members
 * (properties, methods, constants) from the current analysis context.
 *
 * This interface is the Scope's role as an access control checker. It is
 * passed as a parameter to Type methods like getMethod(), getProperty(),
 * getConstant(), etc., so the type system can enforce visibility rules
 * (public/protected/private) based on where the access occurs.
 *
 * The primary implementation is MutatingScope. A secondary implementation,
 * OutOfClassScope, is used when accessing members from outside any class.
 *
 * @api
 */
interface ClassMemberAccessAnswerer
{

	/**
	 * Returns whether the current analysis context is inside a class.
	 *
	 * When true, getClassReflection() is guaranteed to return non-null.
	 * Used to determine if protected/private members are accessible.
	 *
	 * @phpstan-assert-if-true !null $this->getClassReflection()
	 */
	public function isInClass(): bool;

	/**
	 * Returns the ClassReflection of the class the current code is in,
	 * or null if not inside a class.
	 *
	 * Used together with property/method/constant reflections to determine
	 * whether the current context has access to protected or private members.
	 */
	public function getClassReflection(): ?ClassReflection;

	/**
	 * @deprecated Use canReadProperty() or canWriteProperty()
	 */
	public function canAccessProperty(PropertyReflection $propertyReflection): bool;

	/**
	 * Returns whether the current context can read the given property.
	 *
	 * Checks visibility rules: public properties are always readable,
	 * protected properties are readable from the same class or subclasses,
	 * and private properties are only readable from the declaring class.
	 *
	 * Also accounts for PHP 8.4 asymmetric visibility where a property
	 * may have different read and write visibility.
	 */
	public function canReadProperty(ExtendedPropertyReflection $propertyReflection): bool;

	/**
	 * Returns whether the current context can write to the given property.
	 *
	 * Like canReadProperty(), but checks write visibility instead.
	 * With PHP 8.4 asymmetric visibility, a property like
	 * `public private(set) string $name` is publicly readable but only
	 * privately writable.
	 */
	public function canWriteProperty(ExtendedPropertyReflection $propertyReflection): bool;

	/**
	 * Returns whether the current context can call the given method.
	 *
	 * Checks visibility rules: public methods are always callable,
	 * protected methods are callable from the same class or subclasses,
	 * and private methods are only callable from the declaring class.
	 */
	public function canCallMethod(MethodReflection $methodReflection): bool;

	/**
	 * Returns whether the current context can access the given class constant.
	 *
	 * Checks visibility rules: public constants are always accessible,
	 * protected constants are accessible from the same class or subclasses,
	 * and private constants are only accessible from the declaring class.
	 */
	public function canAccessConstant(ClassConstantReflection $constantReflection): bool;

}
