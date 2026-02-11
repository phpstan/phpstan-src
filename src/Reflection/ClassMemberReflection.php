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
 * @api-do-not-implement
 */
interface ClassMemberReflection
{

	/**
	 * For inherited members, this returns the original declaring class,
	 * not the class where the member was accessed.
	 */
	public function getDeclaringClass(): ClassReflection;

	public function isStatic(): bool;

	public function isPrivate(): bool;

	public function isPublic(): bool;

	public function getDocComment(): ?string;

}
