<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

/**
 * The validated result of the merged `attributeServicesDirectories` section.
 */
final class ResolvedAttributeServicesDirectories
{

	/**
	 * @param list<ResolvedAttributeServicesDirectory> $directories
	 */
	public function __construct(public array $directories)
	{
	}

	public static function createEmpty(): self
	{
		return new self([]);
	}

	/**
	 * @return list<string>
	 */
	public function getDirectoryPaths(): array
	{
		$paths = [];
		foreach ($this->directories as $directory) {
			$paths[] = $directory->directory;
		}

		return $paths;
	}

	/**
	 * @return array<string, string>
	 */
	public function getCacheKeyComponent(): array
	{
		$component = [];
		foreach ($this->directories as $directory) {
			foreach ($directory->cacheKeyComponent as $key => $value) {
				$component[$key] = $value;
			}
		}

		return $component;
	}

}
