<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Autowires constructor parameters in service classes using #[AutowiredService],
 * #[GeneratedFactory], #[RegisteredRule] or #[RegisteredCollector] attributes.
 *
 * If ref is omitted, it looks for parameter of the same name.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_PARAMETER)]
final class AutowiredParameter
{

	public function __construct(public ?string $ref = null)
	{
	}

}
