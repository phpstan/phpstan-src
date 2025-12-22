<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use function array_merge;
use function strtolower;

final class DynamicReturnTypeExtensionRegistry
{

	/** @var DynamicMethodReturnTypeExtension[][]|null */
	private ?array $dynamicMethodReturnTypeExtensionsByClass = null;

	/** @var DynamicStaticMethodReturnTypeExtension[][]|null */
	private ?array $dynamicStaticMethodReturnTypeExtensionsByClass = null;

	/** @var array<string, list<DynamicFunctionReturnTypeExtension>> */
	private array $dynamicReturnTypeExtensionsByFunction = [];

	/**
	 * @param DynamicMethodReturnTypeExtension[] $dynamicMethodReturnTypeExtensions
	 * @param DynamicStaticMethodReturnTypeExtension[] $dynamicStaticMethodReturnTypeExtensions
	 * @param DynamicFunctionReturnTypeExtension[] $dynamicFunctionReturnTypeExtensions
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private array $dynamicMethodReturnTypeExtensions,
		private array $dynamicStaticMethodReturnTypeExtensions,
		private array $dynamicFunctionReturnTypeExtensions,
	)
	{
	}

	/**
	 * @return DynamicMethodReturnTypeExtension[]
	 */
	public function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
	{
		if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->dynamicMethodReturnTypeExtensions as $extension) {
				$byClass[strtolower($extension->getClass())][] = $extension;
			}

			$this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
		}
		return $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
	}

	/**
	 * @return DynamicStaticMethodReturnTypeExtension[]
	 */
	public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className): array
	{
		if ($this->dynamicStaticMethodReturnTypeExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->dynamicStaticMethodReturnTypeExtensions as $extension) {
				$byClass[strtolower($extension->getClass())][] = $extension;
			}

			$this->dynamicStaticMethodReturnTypeExtensionsByClass = $byClass;
		}
		return $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
	}

	/**
	 * @param DynamicMethodReturnTypeExtension[][]|DynamicStaticMethodReturnTypeExtension[][] $extensions
	 * @return mixed[]
	 */
	private function getDynamicExtensionsForType(array $extensions, string $className): array
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$extensionsForClass = [[]];
		$class = $this->reflectionProvider->getClass($className);
		foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
			$extensionClassName = strtolower($extensionClassName);
			if (!isset($extensions[$extensionClassName])) {
				continue;
			}

			$extensionsForClass[] = $extensions[$extensionClassName];
		}

		return array_merge(...$extensionsForClass);
	}

	/**
	 * @return DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(FunctionReflection $functionReflection): array
	{
		$functionName = $functionReflection->getName();
		if (isset($this->dynamicReturnTypeExtensionsByFunction[$functionName])) {
			return $this->dynamicReturnTypeExtensionsByFunction[$functionName];
		}

		$supportedFunctions = [];
		foreach ($this->dynamicFunctionReturnTypeExtensions as $dynamicFunctionReturnTypeExtension) {
			if (!$dynamicFunctionReturnTypeExtension->isFunctionSupported($functionReflection)) {
				continue;
			}

			$supportedFunctions[] = $dynamicFunctionReturnTypeExtension;
		}

		return $this->dynamicReturnTypeExtensionsByFunction[$functionName] = $supportedFunctions;
	}

}
