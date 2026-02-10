<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * Base interface for all class members: properties, methods, and constants.
 *
 * Provides common metadata shared by all class members — their declaring class,
 * visibility (public/private/protected), static-ness, and raw PHPDoc comment.
 *
 * This is the parent interface for PropertyReflection, MethodReflection, and
 * (via ConstantReflection) ClassConstantReflection. Extension developers typically
 * work with the more specific child interfaces.
 *
 * @api
 */
interface ClassMemberReflection
{

	/**
	 * Returns the class where this member is declared.
	 *
	 * For inherited members, this returns the original declaring class,
	 * not the class where the member was accessed.
	 */
	public function getDeclaringClass(): ClassReflection;

	/** Whether this member is declared static. */
	public function isStatic(): bool;

	/** Whether this member has private visibility. */
	public function isPrivate(): bool;

	/** Whether this member has public visibility. */
	public function isPublic(): bool;

	/**
	 * Returns the raw PHPDoc comment for this member, or null if none exists.
	 *
	 * This is the unparsed comment string including the /** delimiters.
	 */
	public function getDocComment(): ?string;

}
