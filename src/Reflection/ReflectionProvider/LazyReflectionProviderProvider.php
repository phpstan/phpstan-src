<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;

#[AutowiredService(as: ReflectionProviderProvider::class)]
final class LazyReflectionProviderProvider implements ReflectionProviderProvider
{

	public function __construct(private Container $container)
	{
	}

	public function getReflectionProvider(): ReflectionProvider
	{
		return $this->container->getByType(ReflectionProvider::class);
	}

}
