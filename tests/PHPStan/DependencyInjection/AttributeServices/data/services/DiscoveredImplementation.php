<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService(as: DiscoveredInterface::class)]
final class DiscoveredImplementation implements DiscoveredInterface
{

}
