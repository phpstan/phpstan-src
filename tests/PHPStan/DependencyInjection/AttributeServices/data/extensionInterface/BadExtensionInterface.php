<?php declare(strict_types = 1);

namespace AttributeServicesFixtures\ExtensionInterface;

use PHPStan\DependencyInjection\ExtensionInterface;

#[ExtensionInterface(tag: 'attributeServicesFixtures.badTag')]
interface BadExtensionInterface
{

}
