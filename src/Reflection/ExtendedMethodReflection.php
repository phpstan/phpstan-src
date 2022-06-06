<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * The purpose of this interface is to be able to
 * answer more questions about methods
 * without breaking backward compatibility
 * with existing MethodsClassReflectionExtension.
 *
 * Developers are meant to only use the MethodReflection
 * and its methods in their code.
 *
 * Methods on ExtendedMethodReflection are subject to change.
 */
interface ExtendedMethodReflection extends MethodReflection
{

	public function acceptsNamedArguments(): bool;

}
