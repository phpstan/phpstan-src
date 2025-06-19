<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;

#[AutowiredService(as: OperatorTypeSpecifyingExtensionRegistryProvider::class)]
final class LazyOperatorTypeSpecifyingExtensionRegistryProvider implements OperatorTypeSpecifyingExtensionRegistryProvider
{

	private ?OperatorTypeSpecifyingExtensionRegistry $registry = null;

	public function __construct(private Container $container)
	{
	}

	public function getRegistry(): OperatorTypeSpecifyingExtensionRegistry
	{
		if ($this->registry === null) {
			$this->registry = new OperatorTypeSpecifyingExtensionRegistry(
				$this->container->getServicesByTag(BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG),
			);
		}

		return $this->registry;
	}

}
