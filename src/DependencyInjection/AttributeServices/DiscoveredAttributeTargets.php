<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;

/**
 * Attribute targets found in the directories listed
 * in the `attributeServicesDirectories` section.
 */
final class DiscoveredAttributeTargets
{

	/**
	 * @param array<class-string, list<TargetClass<object>>> $targetClasses
	 * @param array<class-string, list<TargetMethodParameter<object>>> $targetMethodParameters
	 */
	public function __construct(
		public array $targetClasses,
		public array $targetMethodParameters,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self([], []);
	}

}
