<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

interface DiscoveredValueFactory
{

	public function create(string $name): DiscoveredValue;

}
