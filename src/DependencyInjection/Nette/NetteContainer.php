<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Nette;

use PHPStan\DependencyInjection\AutowiredExtensionsExtension;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\DependencyInjection\ParameterNotFoundException;
use function array_key_exists;
use function array_keys;
use function array_map;
use function sprintf;

/**
 * @internal
 */
#[AutowiredService(as: NetteContainer::class)]
final class NetteContainer implements Container
{

	/** @var mixed[] */
	private ?array $parameters = null;

	public function __construct(
		private readonly \Nette\DI\Container $container,
	)
	{
	}

	public function hasService(string $serviceName): bool
	{
		return $this->container->hasService($serviceName);
	}

	/**
	 * @return mixed
	 */
	public function getService(string $serviceName)
	{
		try {
			return $this->container->getService($serviceName);
		} catch (\Nette\DI\MissingServiceException $e) {
			throw new MissingServiceException($e->getMessage(), previous: $e);
		}
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getByType(string $className)
	{
		try {
			return $this->container->getByType($className);
		} catch (\Nette\DI\MissingServiceException $e) {
			throw new MissingServiceException($e->getMessage(), previous: $e);
		}
	}

	/**
	 * @param class-string $className
	 * @return string[]
	 */
	public function findServiceNamesByType(string $className): array
	{
		return $this->container->findByType($className);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $extensionInterfaceName
	 * @return ExtensionsCollection<T>
	 */
	public function getExtensionsCollection(string $extensionInterfaceName): ExtensionsCollection
	{
		$serviceName = AutowiredExtensionsExtension::getCollectionServiceName($extensionInterfaceName);
		if (!$this->container->hasService($serviceName)) {
			throw new MissingServiceException(sprintf('%s is not an extension interface marked with the #[ExtensionInterface] attribute.', $extensionInterfaceName));
		}

		/** @var ExtensionsCollection<T> */
		return $this->getService($serviceName);
	}

	/**
	 * @return mixed[]
	 */
	public function getServicesByTag(string $tagName): array
	{
		return $this->tagsToServices($this->container->findByTag($tagName));
	}

	/**
	 * @return mixed[]
	 */
	public function getParameters(): array
	{
		return $this->parameters ??= $this->container->getParameters();
	}

	public function hasParameter(string $parameterName): bool
	{
		$parameters = $this->parameters ??= $this->container->getParameters();

		return array_key_exists($parameterName, $parameters);
	}

	/**
	 * @return mixed
	 */
	public function getParameter(string $parameterName)
	{
		$parameters = $this->parameters ??= $this->container->getParameters();

		if (!array_key_exists($parameterName, $parameters)) {
			throw new ParameterNotFoundException($parameterName);
		}

		return $parameters[$parameterName];
	}

	/**
	 * @param mixed[] $tags
	 * @return mixed[]
	 */
	private function tagsToServices(array $tags): array
	{
		return array_map(fn (string $serviceName) => $this->getService($serviceName), array_keys($tags));
	}

}
