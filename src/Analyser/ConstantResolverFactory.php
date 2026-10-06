<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\ConfiguredPhpIntSizeHelper;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;

#[AutowiredService]
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
		$composerFactory = $this->container->getByType(ComposerPhpVersionFactory::class);

		return new ConstantResolver(
			$this->reflectionProviderProvider,
			$this->container->getParameter('dynamicConstantNames'),
			new ConfiguredPhpVersionRangeHelper(
				$this->container->getParameter('phpVersion'),
				$composerFactory,
			),
			$this->container->getByType(ConfiguredPhpIntSizeHelper::class),
			$this->container,
		);
	}

}
