<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\DependencyInjection\Container;

class LazyClosureTypeChangingExtensionProvider implements ClosureTypeChangingExtensionProvider
{

	public const FUNCTION_TAG = 'phpstan.functionClosureTypeChangingExtension';
	public const METHOD_TAG = 'phpstan.methodClosureTypeChangingExtension';
	public const STATIC_METHOD_TAG = 'phpstan.staticMethodClosureTypeChangingExtension';

	public function __construct(private Container $container)
	{
	}

	public function getFunctionClosureTypeChangingExtensions(): array
	{
		return $this->container->getServicesByTag(self::FUNCTION_TAG);
	}

	public function getMethodClosureTypeChangingExtensions(): array
	{
		return $this->container->getServicesByTag(self::METHOD_TAG);
	}

	public function getStaticMethodClosureTypeChangingExtensions(): array
	{
		return $this->container->getServicesByTag(self::STATIC_METHOD_TAG);
	}

}
