<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Nette;

use PHPStan\DependencyInjection\Container;

/**
 * @internal
 */
class NetteContainer implements Container
{

	private \Nette\DI\Container $container;

	public function __construct(\Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	public function hasService(string $serviceName): bool
	{
		return $this->container->hasService($serviceName);
	}

	/**
	 * @param string $serviceName
	 * @return mixed
	 */
	public function getService(string $serviceName)
	{
		return $this->container->getService($serviceName);
	}

	/**
	 * @phpstan-template T of object
	 * @phpstan-param class-string<T> $className
	 * @phpstan-return T
	 * @return mixed
	 */
	public function getByType(string $className)
	{
		/**
		 * @var T|null $service
		 */
		$service = $this->container->getByType($className);
		if ($service === null) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Service for class %s not found.', $className));
		}
		return $service;
	}

	/**
	 * @param string $className
	 * @return string[]
	 */
	public function findServiceNamesByType(string $className): array
	{
		return $this->container->findByType($className);
	}

	/**
	 * @param string $tagName
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
		return $this->container->parameters;
	}

	public function hasParameter(string $parameterName): bool
	{
		return array_key_exists($parameterName, $this->container->parameters);
	}

	/**
	 * @param string $parameterName
	 * @return mixed
	 */
	public function getParameter(string $parameterName)
	{
		if (!$this->hasParameter($parameterName)) {
			throw new \PHPStan\DependencyInjection\ParameterNotFoundException($parameterName);
		}

		return $this->container->parameters[$parameterName];
	}

	/**
	 * @param mixed[] $tags
	 * @return mixed[]
	 */
	private function tagsToServices(array $tags): array
	{
		return array_map(function (string $serviceName) {
			return $this->getService($serviceName);
		}, array_keys($tags));
	}

}
