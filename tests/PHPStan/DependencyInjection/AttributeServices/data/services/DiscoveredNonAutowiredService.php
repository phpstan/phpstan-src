<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PHPStan\DependencyInjection\NonAutowiredService;

#[NonAutowiredService(name: 'attributeServicesFixtures.nonAutowired')]
final class DiscoveredNonAutowiredService
{

}
