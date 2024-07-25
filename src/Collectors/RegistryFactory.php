<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PHPStan\DependencyInjection\Container;

final class RegistryFactory
{

	public const COLLECTOR_TAG = 'phpstan.collector';

	public function __construct(private Container $container)
	{
	}

	public function create(): Registry
	{
		return new Registry(
			$this->container->getServicesByTag(self::COLLECTOR_TAG),
		);
	}

}
