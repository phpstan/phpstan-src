<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;

final class ConstantResolverFactory
{

	public function __construct(
		private ReflectionProviderProvider $reflectionProviderProvider,
		private Container $container,
	)
	{
	}

	public function create(): ConstantResolver
	{
		return new ConstantResolver(
			$this->reflectionProviderProvider,
			$this->container->getParameter('dynamicConstantNames'),
		);
	}

}
