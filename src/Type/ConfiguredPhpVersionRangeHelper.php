<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
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
	 * @return array{PhpVersion, PhpVersion}
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
