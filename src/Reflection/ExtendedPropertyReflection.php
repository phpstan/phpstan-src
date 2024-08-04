<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * The purpose of this interface is to be able to
 * answer more questions about properties
 * without breaking backward compatibility
 * with existing PropertiesClassReflectionExtension.
 *
 * Developers are meant to only implement PropertyReflection
 * and its methods in their code.
 *
 * New methods on ExtendedPropertyReflection will be added
 * in minor versions.
 *
 * @api
 */
interface ExtendedPropertyReflection extends PropertyReflection
{

}
