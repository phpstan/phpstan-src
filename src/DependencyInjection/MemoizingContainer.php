<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

#[AutowiredService(as: Container::class)]
final class MemoizingContainer implements Container
{

	/** @var array<string, mixed> */
	private array $servicesByType = [];

	/** @var array<string, mixed> */
	private array $servicesByName = [];

	/** @var array<string, mixed> */
	private array $servicesByTag = [];

	/** @var array<class-string, list<object>> */
	private array $extensionsByInterface = [];

	public function __construct(
		#[AutowiredParameter(ref: '@PHPStan\DependencyInjection\Nette\NetteContainer')]
		private Container $originalContainer,
	)
	{
	}

	public function hasService(string $serviceName): bool
	{
		return $this->originalContainer->hasService($serviceName);
	}

	public function getService(string $serviceName)
	{
		return $this->servicesByName[$serviceName] ??= $this->originalContainer->getService($serviceName);
	}

	public function getByType(string $className)
	{
		return $this->servicesByType[$className] ??= $this->originalContainer->getByType($className);
	}

	public function findServiceNamesByType(string $className): array
	{
		return $this->originalContainer->findServiceNamesByType($className);
	}

	public function getServicesByTag(string $tagName): array
	{
		return $this->servicesByTag[$tagName] ??= $this->originalContainer->getServicesByTag($tagName);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $interfaceName
	 * @return list<T>
	 */
	public function getExtensions(string $interfaceName): array
	{
		/** @var list<T> */
		return $this->extensionsByInterface[$interfaceName] ??= $this->originalContainer->getExtensions($interfaceName);
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
