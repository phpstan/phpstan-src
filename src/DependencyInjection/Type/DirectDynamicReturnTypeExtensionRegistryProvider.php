<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

/**
 * @internal
 */
class DirectDynamicReturnTypeExtensionRegistryProvider implements DynamicReturnTypeExtensionRegistryProvider
{

	/** @var \PHPStan\Type\DynamicMethodReturnTypeExtension[] */
	private array $dynamicMethodReturnTypeExtensions;

	/** @var \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] */
	private array $dynamicStaticMethodReturnTypeExtensions;

	/** @var \PHPStan\Type\DynamicFunctionReturnTypeExtension[] */
	private array $dynamicFunctionReturnTypeExtensions;

	private Broker $broker;

	private ReflectionProvider $reflectionProvider;

	/**
	 * @param \PHPStan\Type\DynamicMethodReturnTypeExtension[] $dynamicMethodReturnTypeExtensions
	 * @param \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] $dynamicStaticMethodReturnTypeExtensions
	 * @param \PHPStan\Type\DynamicFunctionReturnTypeExtension[] $dynamicFunctionReturnTypeExtensions
	 */
	public function __construct(
		array $dynamicMethodReturnTypeExtensions,
		array $dynamicStaticMethodReturnTypeExtensions,
		array $dynamicFunctionReturnTypeExtensions
	)
	{
		$this->dynamicMethodReturnTypeExtensions = $dynamicMethodReturnTypeExtensions;
		$this->dynamicStaticMethodReturnTypeExtensions = $dynamicStaticMethodReturnTypeExtensions;
		$this->dynamicFunctionReturnTypeExtensions = $dynamicFunctionReturnTypeExtensions;
	}

	public function setBroker(Broker $broker): void
	{
		$this->broker = $broker;
	}

	public function setReflectionProvider(ReflectionProvider $reflectionProvider): void
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function addDynamicMethodReturnTypeExtension(DynamicMethodReturnTypeExtension $extension): void
	{
		$this->dynamicMethodReturnTypeExtensions[] = $extension;
	}

	public function addDynamicStaticMethodReturnTypeExtension(DynamicStaticMethodReturnTypeExtension $extension): void
	{
		$this->dynamicStaticMethodReturnTypeExtensions[] = $extension;
	}

	public function addDynamicFunctionReturnTypeExtension(DynamicFunctionReturnTypeExtension $extension): void
	{
		$this->dynamicFunctionReturnTypeExtensions[] = $extension;
	}

	public function getRegistry(): DynamicReturnTypeExtensionRegistry
	{
		return new DynamicReturnTypeExtensionRegistry(
			$this->broker,
			$this->reflectionProvider,
			$this->dynamicMethodReturnTypeExtensions,
			$this->dynamicStaticMethodReturnTypeExtensions,
			$this->dynamicFunctionReturnTypeExtensions
		);
	}

}
