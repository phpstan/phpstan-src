<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function is_array;

#[AutowiredService]
final class ConfiguredPhpVersionRangeHelper
{

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%phpVersion%')]
		private int|array|null $configPhpVersion,
		private ComposerPhpVersionFactory $composerPhpVersionFactory,
	)
	{
	}

	/**
	 * Returns the php version range analysis is running against.
	 * Source is either the NEON config phpVersion min/max values, or the projects composer.json php version constraint.
	 *
	 * @return array{PhpVersion|null, PhpVersion|null}
	 */
	public function getVersionRange(): array
	{
		if (is_array($this->configPhpVersion)) {
			$minVersion = new PhpVersion($this->configPhpVersion['min']);
			$maxVersion = new PhpVersion($this->configPhpVersion['max']);
		} else {
			$minVersion = $this->composerPhpVersionFactory->getMinVersion();
			$maxVersion = $this->composerPhpVersionFactory->getMaxVersion();
		}

		return [$minVersion, $maxVersion];
	}

}
