<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;

#[AutowiredService(as: ParameterClosureThisExtensionProvider::class)]
final class LazyParameterClosureThisExtensionProvider implements ParameterClosureThisExtensionProvider
{

	public const FUNCTION_TAG = 'phpstan.functionParameterClosureThisExtension';
	public const METHOD_TAG = 'phpstan.methodParameterClosureThisExtension';
	public const STATIC_METHOD_TAG = 'phpstan.staticMethodParameterClosureThisExtension';

	/** @var FunctionParameterClosureThisExtension[]|null */
	private ?array $functionExtensions = null;

	/** @var MethodParameterClosureThisExtension[]|null */
	private ?array $methodExtensions = null;

	/** @var StaticMethodParameterClosureThisExtension[]|null */
	private ?array $staticMethodExtensions = null;

	public function __construct(private Container $container)
	{
	}

	public function getFunctionParameterClosureThisExtensions(): array
	{
		return $this->functionExtensions ??= $this->container->getServicesByTag(self::FUNCTION_TAG);
	}

	public function getMethodParameterClosureThisExtensions(): array
	{
		return $this->methodExtensions ??= $this->container->getServicesByTag(self::METHOD_TAG);
	}

	public function getStaticMethodParameterClosureThisExtensions(): array
	{
		return $this->staticMethodExtensions ??= $this->container->getServicesByTag(self::STATIC_METHOD_TAG);
	}

}
