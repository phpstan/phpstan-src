<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

class MemoizingContainer implements Container
{

	private Container $originalContainer;

	/** @var array<string, mixed> */
	private array $servicesByType = [];

	public function __construct(Container $originalContainer)
	{
		$this->originalContainer = $originalContainer;
	}

	public function hasService(string $serviceName): bool
	{
		return $this->originalContainer->hasService($serviceName);
	}

	/**
	 * @param string $serviceName
	 * @return mixed
	 */
	public function getService(string $serviceName)
	{
		return $this->originalContainer->getService($serviceName);
	}

	/**
	 * @phpstan-template T of object
	 * @phpstan-param class-string<T> $className
	 * @phpstan-return T
	 * @return mixed
	 */
	public function getByType(string $className)
	{
		if (array_key_exists($className, $this->servicesByType)) {
			return $this->servicesByType[$className];
		}

		$service = $this->originalContainer->getByType($className);
		$this->servicesByType[$className] = $service;

		return $service;
	}

	public function findServiceNamesByType(string $className): array
	{
		return $this->originalContainer->findServiceNamesByType($className);
	}

	public function getServicesByTag(string $tagName): array
	{
		return $this->originalContainer->getServicesByTag($tagName);
	}

	public function getParameters(): array
	{
		return $this->originalContainer->getParameters();
	}

	public function hasParameter(string $parameterName): bool
	{
		return $this->originalContainer->hasParameter($parameterName);
	}

	/**
	 * @param string $parameterName
	 * @return mixed
	 * @throws ParameterNotFoundException
	 */
	public function getParameter(string $parameterName)
	{
		return $this->originalContainer->getParameter($parameterName);
	}

}
