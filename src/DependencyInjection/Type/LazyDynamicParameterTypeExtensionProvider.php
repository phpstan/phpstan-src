<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\DependencyInjection\Container;

final class LazyDynamicParameterTypeExtensionProvider implements DynamicParameterTypeExtensionProvider
{

	public const FUNCTION_TAG = 'phpstan.functionDynamicParameterTypeExtension';
	public const METHOD_TAG = 'phpstan.methodDynamicParameterTypeExtension';
	public const STATIC_METHOD_TAG = 'phpstan.staticMethodDynamicParameterTypeExtension';

	public function __construct(private Container $container)
	{
	}

	public function getFunctionDynamicParameterTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::FUNCTION_TAG);
	}

	public function getMethodDynamicParameterTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::METHOD_TAG);
	}

	public function getStaticMethodDynamicParameterTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::STATIC_METHOD_TAG);
	}

}
