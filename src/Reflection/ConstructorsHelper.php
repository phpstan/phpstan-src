<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use ReflectionException;
use function array_key_exists;
use function explode;

#[AutowiredService]
final class ConstructorsHelper
{

	/** @var array<string, list<string>> */
	private array $additionalConstructorsCache = [];

	/**
	 * @param ExtensionsCollection<AdditionalConstructorsExtension> $additionalConstructorsExtensions
	 * @param list<string> $additionalConstructors
	 */
	public function __construct(
		#[AutowiredExtensions(of: AdditionalConstructorsExtension::class)]
		private ExtensionsCollection $additionalConstructorsExtensions,
		#[AutowiredParameter]
		private array $additionalConstructors,
	)
	{
	}

	/**
	 * @return list<string>
	 */
	public function getConstructors(ClassReflection $classReflection): array
	{
		if (array_key_exists($classReflection->getName(), $this->additionalConstructorsCache)) {
			return $this->additionalConstructorsCache[$classReflection->getName()];
		}
		$constructors = [];
		if ($classReflection->hasConstructor()) {
			$constructors[] = $classReflection->getConstructor()->getName();
		}

		$extensions = $this->additionalConstructorsExtensions->getAll();
		foreach ($extensions as $extension) {
			$extensionConstructors = $extension->getAdditionalConstructors($classReflection);
			foreach ($extensionConstructors as $extensionConstructor) {
				$constructors[] = $extensionConstructor;
			}
		}

		$nativeReflection = $classReflection->getNativeReflection();
		foreach ($this->additionalConstructors as $additionalConstructor) {
			[$className, $methodName] = explode('::', $additionalConstructor);
			if ($methodName === null) {
				continue;
			}
			if (!$nativeReflection->hasMethod($methodName)) {
				continue;
			}
			$nativeMethod = $nativeReflection->getMethod($methodName);
			if ($nativeMethod->getDeclaringClass()->getName() !== $nativeReflection->getName()) {
				continue;
			}

			try {
				$prototype = $nativeMethod->getPrototype();
			} catch (ReflectionException) {
				$prototype = $nativeMethod;
			}

			if ($prototype->getDeclaringClass()->getName() !== $className) {
				continue;
			}

			$constructors[] = $methodName;
		}

		$this->additionalConstructorsCache[$classReflection->getName()] = $constructors;

		return $constructors;
	}

}
