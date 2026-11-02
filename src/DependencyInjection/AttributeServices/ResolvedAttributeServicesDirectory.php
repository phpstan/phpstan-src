<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

/**
 * One validated directory from the `attributeServicesDirectories` section together
 * with everything discovery and container-cache invalidation need to know about it.
 */
final class ResolvedAttributeServicesDirectory
{

	/**
	 * @param string $directory normalized with forward slashes, no trailing slash
	 * @param string|null $packageName owning Composer package, null for a directory of the project itself
	 * @param array<string, string> $cacheKeyComponent the directory's contribution to the container cache key -
	 *                                                 a package version token, or per-file content hashes
	 */
	public function __construct(
		public string $directory,
		public ?string $packageName,
		public array $cacheKeyComponent,
	)
	{
	}

}
