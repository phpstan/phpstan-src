<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;

class ConstantResolverFactory
{

	public function __construct(
		private ReflectionProviderProvider $reflectionProviderProvider,
		private Container $container,
	)
	{
	}

	public function create(): ConstantResolver
	{
		$composerFactory = $this->container->getByType(ComposerPhpVersionFactory::class);

		return new ConstantResolver(
			$this->reflectionProviderProvider,
			$this->container->getParameter('dynamicConstantNames'),
			$composerFactory->getMinVersion(),
			$composerFactory->getMaxVersion(),
		);
	}

}
