<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\DependencyInjection\Container;

final class LazyDynamicThrowTypeExtensionProvider implements DynamicThrowTypeExtensionProvider
{

	public const FUNCTION_TAG = 'phpstan.dynamicFunctionThrowTypeExtension';
	public const METHOD_TAG = 'phpstan.dynamicMethodThrowTypeExtension';
	public const STATIC_METHOD_TAG = 'phpstan.dynamicStaticMethodThrowTypeExtension';

	public function __construct(private Container $container)
	{
	}

	public function getDynamicFunctionThrowTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::FUNCTION_TAG);
	}

	public function getDynamicMethodThrowTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::METHOD_TAG);
	}

	public function getDynamicStaticMethodThrowTypeExtensions(): array
	{
		return $this->container->getServicesByTag(self::STATIC_METHOD_TAG);
	}

}
