<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\NonAutowiredService;

#[AutowiredService(name: 'attributeServicesFixtures.staticallyCreated', factory: 'AttributeServicesFixtures\DiscoveredStaticallyCreated::create')]
#[NonAutowiredService(name: 'attributeServicesFixtures.staticallyCreatedNonAutowired', factory: 'AttributeServicesFixtures\DiscoveredStaticallyCreated::create')]
final class DiscoveredStaticallyCreated
{

	private function __construct()
	{
	}

	public static function create(): self
	{
		return new self();
	}

}
