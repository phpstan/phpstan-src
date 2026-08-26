<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PHPStan\DependencyInjection\ExtensionInterface;

#[ExtensionInterface(tag: 'phpstan.tests.discoveredExtension')]
interface DiscoveredExtension
{

	public function getName(): string;

}
