<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use Attribute;

/**
 * Marks a class or interface declared in a stub file that only became generic
 * after it had already been released as non-generic.
 *
 * Requiring the type arguments right away would mean reporting missingType.generics
 * in code that used to be fine, so the class is added to the
 * featureToggles.skipCheckGenericClasses parameter and the check is only performed
 * with bleeding edge enabled.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and SkipCheckGenericClassesExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class RequireGenericsInBleedingEdgeOnly
{

}
