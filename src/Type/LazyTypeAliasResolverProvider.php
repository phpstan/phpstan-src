<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\DependencyInjection\Container;

final class LazyTypeAliasResolverProvider implements TypeAliasResolverProvider
{

	public function __construct(private Container $container)
	{
	}

	public function getTypeAliasResolver(): TypeAliasResolver
	{
		return $this->container->getByType(TypeAliasResolver::class);
	}

}
