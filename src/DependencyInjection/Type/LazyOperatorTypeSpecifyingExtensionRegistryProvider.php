<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;

class LazyOperatorTypeSpecifyingExtensionRegistryProvider implements OperatorTypeSpecifyingExtensionRegistryProvider
{

	/** @var \PHPStan\DependencyInjection\Container */
	private $container;

	/** @var \PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry|null */
	private $registry;

	public function __construct(\PHPStan\DependencyInjection\Container $container)
	{
		$this->container = $container;
	}

	public function getRegistry(): OperatorTypeSpecifyingExtensionRegistry
	{
		if ($this->registry === null) {
			$this->registry = new OperatorTypeSpecifyingExtensionRegistry(
				$this->container->getByType(Broker::class),
				$this->container->getServicesByTag(BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG)
			);
		}

		return $this->registry;
	}

}
