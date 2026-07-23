<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Attribute;

/**
 * Marks a class or interface where `instanceof` in user code
 * is error-prone and deprecated.
 *
 * Reported by ApiInstanceofTypeRule. The optional $insteadUse
 * describes the Type API to query instead.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class InstanceofDeprecated
{

	public function __construct(public ?string $insteadUse = null)
	{
	}

}
