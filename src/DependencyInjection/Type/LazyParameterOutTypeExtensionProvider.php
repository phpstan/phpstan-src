<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\DependencyInjection\Container;

class LazyParameterOutTypeExtensionProvider implements DynamicParameterOutTypeExtensionProvider
{

	public const FUNCTION_TAG = 'phpstan.dynamicFunctionParameterOutTypeExtension';
	public const METHOD_TAG = 'phpstan.dynamicMethodParameterOutTypeExtension';
	public const STATIC_METHOD_TAG = 'phpstan.dynamicStaticMethodParameterOutTypeExtension';

	public function __construct(private Container $container)
	{
	}

	public function getDynamicFunctionParameterOutTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::FUNCTION_TAG);
	}

	public function getDynamicMethodParameterOutTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::METHOD_TAG);
	}

	public function getDynamicStaticMethodParameterOutTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::STATIC_METHOD_TAG);
	}

}
