<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

/**
 * One installed Composer package of the analysed project.
 */
final class ComposerPackage
{

	/**
	 * @param string $installPath normalized with forward slashes, no trailing slash
	 * @param string|null $cacheToken version identity usable as a cache key, or null when the
	 *                                installed files can change without the version changing
	 *                                (path repositories, missing reference)
	 */
	public function __construct(
		public string $name,
		public string $installPath,
		public ?string $cacheToken,
		public AutoloadRules $autoload,
	)
	{
	}

}
