<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

#[Conditional(
	attribute: ['olvlvl\ComposerAttributeCollector\NewExpression' => [AutowiredService::class, []]],
	if: 'not(%reportUnmatchedIgnoredErrors%)',
)]
final class DiscoveredConditionalService implements ReadWritePropertiesExtension
{

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

}
