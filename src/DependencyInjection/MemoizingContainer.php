<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use function array_key_exists;

final class MemoizingContainer implements Container
{

	/** @var array<string, mixed> */
	private array $servicesByType = [];

	public function __construct(private Container $originalContainer)
	{
	}

	public function hasService(string $serviceName): bool
	{
		return $this->originalContainer->hasService($serviceName);
	}

	public function getService(string $serviceName)
	{
		return $this->originalContainer->getService($serviceName);
	}

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

	public function getParameter(string $parameterName)
	{
		return $this->originalContainer->getParameter($parameterName);
	}

}
