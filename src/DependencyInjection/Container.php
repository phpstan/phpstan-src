<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

/**
 * @api
 * @api-do-not-implement
 */
interface Container
{

	public function hasService(string $serviceName): bool;

	/**
	 * @return mixed
	 * @throws MissingServiceException
	 */
	public function getService(string $serviceName);

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 * @throws MissingServiceException
	 */
	public function getByType(string $className);

	/**
	 * @param class-string $className
	 * @return string[]
	 */
	public function findServiceNamesByType(string $className): array;

	/**
	 * @return mixed[]
	 */
	public function getServicesByTag(string $tagName): array;

	/**
	 * All extensions registered under the given extension interface,
	 * i.e. all services tagged with the tag the interface declares
	 * with the #[ExtensionInterface] attribute.
	 *
	 * @template T of object
	 * @param class-string<T> $interfaceName
	 * @return list<T>
	 * @throws MissingServiceException
	 */
	public function getExtensions(string $interfaceName): array;

	/**
	 * @return mixed[]
	 */
	public function getParameters(): array;

	public function hasParameter(string $parameterName): bool;

	/**
	 * @return mixed
	 * @throws ParameterNotFoundException
	 */
	public function getParameter(string $parameterName);

}
