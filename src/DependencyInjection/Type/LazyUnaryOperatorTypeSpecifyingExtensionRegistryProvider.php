<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtensionRegistry;

#[AutowiredService(as: UnaryOperatorTypeSpecifyingExtensionRegistryProvider::class)]
final class LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider implements UnaryOperatorTypeSpecifyingExtensionRegistryProvider
{

	private ?UnaryOperatorTypeSpecifyingExtensionRegistry $registry = null;

	public function __construct(private Container $container)
	{
	}

	public function getRegistry(): UnaryOperatorTypeSpecifyingExtensionRegistry
	{
		return $this->registry ??= new UnaryOperatorTypeSpecifyingExtensionRegistry(
			$this->container->getServicesByTag(BrokerFactory::UNARY_OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG),
		);
	}

}
