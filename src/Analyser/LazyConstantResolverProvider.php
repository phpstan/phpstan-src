<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;

class LazyConstantResolverProvider implements ConstantResolverProvider
{

	public function __construct(private Container $container)
	{
	}

	public function getConstantResolver(): ConstantResolver
	{
		return $this->container->getByType(ConstantResolver::class);
	}

}
