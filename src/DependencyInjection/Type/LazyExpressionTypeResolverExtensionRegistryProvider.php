<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Type\ExpressionTypeResolverExtensionRegistry;

final class LazyExpressionTypeResolverExtensionRegistryProvider implements ExpressionTypeResolverExtensionRegistryProvider
{

	private ?ExpressionTypeResolverExtensionRegistry $registry = null;

	public function __construct(private Container $container)
	{
	}

	public function getRegistry(): ExpressionTypeResolverExtensionRegistry
	{
		if ($this->registry === null) {
			$this->registry = new ExpressionTypeResolverExtensionRegistry(
				$this->container->getServicesByTag(BrokerFactory::EXPRESSION_TYPE_RESOLVER_EXTENSION_TAG),
			);
		}

		return $this->registry;
	}

}
