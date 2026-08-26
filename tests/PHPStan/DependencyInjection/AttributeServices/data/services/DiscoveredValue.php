<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\GenerateFactory;

#[GenerateFactory(interface: DiscoveredValueFactory::class)]
final class DiscoveredValue
{

	public function __construct(
		public string $name,
		#[AutowiredParameter(ref: '%tmpDir%')]
		public string $tmpDir,
	)
	{
	}

}
