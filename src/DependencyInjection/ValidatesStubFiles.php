<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a rule in the PHPStan\PhpDoc\StubValidator
 *
 * See https://phpstan.org/user-guide/stub-files
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and StubValidatorRuleServicesExtension (similar to AutowiredAttributeServicesExtension).
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ValidatesStubFiles
{

}
