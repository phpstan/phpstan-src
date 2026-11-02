<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers the class with the wrapped attribute only when the condition is met.
 * Otherwise the class is not registered in the DI container at all.
 *
 * The condition has the same syntax as values in the conditionalTags config section:
 * a parameter reference like '%featureToggles.foo%', its negation 'not(%featureToggles.foo%)',
 * or a list of them that all must be met.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class Conditional
{

	/**
	 * @param string|non-empty-list<string> $if
	 */
	public function __construct(
		public AutowiredService|GenerateFactory|NonAutowiredService|RegisteredCollector|RegisteredRule $attribute,
		public string|array $if,
	)
	{
	}

}
