<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Attribute;

use Attribute;

/**
 * Marks a property the phar build made public that is private in the
 * source. The build inlines single-return getters at their call sites
 * (compiler's PrepareCommand), so the properties those getters read have to
 * be readable from the callers; for PHPStan's own reflection the property
 * stays private (PhpClassReflectionExtension), so code analysed against
 * the phar cannot access it.
 */
#[Attribute(flags: Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class PrivateProperty
{

}
