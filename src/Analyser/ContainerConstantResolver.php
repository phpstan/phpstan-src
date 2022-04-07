<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;

class ContainerConstantResolver extends ConstantResolver
{

	public function __construct(
		ReflectionProvider $reflectionProvider,
		private Container $container,
	)
	{
		parent::__construct($reflectionProvider);
	}

	protected function getDynamicConstantNames(): array
	{
		return $this->container->getParameter('dynamicConstantNames');
	}

}
