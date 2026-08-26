<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class TestedDiscoveredExtension implements DiscoveredExtension
{

	public function getName(): string
	{
		return 'discovered';
	}

}
