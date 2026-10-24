<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\ComposerHelper;
use function count;
use function end;
use function is_string;
use function min;

#[AutowiredService]
final class ComposerPhpVersionFactory
{

	private ?PhpVersion $minVersion = null;

	private ?PhpVersion $maxVersion = null;

	private bool $initialized = false;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter(ref: '%featureToggles.composerPhp64Bit%')]
		private bool $composerPhp64Bit = false,
	)
	{
	}

	private function initializeVersions(): void
	{
		$this->initialized = true;

		// don't limit minVersion... PHPStan can analyze even PHP5
		$this->maxVersion = new PhpVersion(PhpVersionFactory::MAX_PHP_VERSION);

		// fallback to composer.json based php-version constraints
		$composerPhpVersions = $this->getComposerRequireVersions();
		if (count($composerPhpVersions) === 0) {
			return;
		}

		$parser = new ComposerPhpVersionParser();
		[$minVersion, $maxVersion] = $parser->parse($composerPhpVersions, static function (string $version, int $versionId, bool $isMaxVersion): PhpVersion {
			if ($isMaxVersion && $version === '6.0.0.0-dev') {
				$versionId = min($versionId, PhpVersionFactory::MAX_PHP5_VERSION);
			} elseif ($isMaxVersion && $version === '8.0.0.0-dev') {
				$versionId = min($versionId, PhpVersionFactory::MAX_PHP7_VERSION);
			} else {
				$versionId = min($versionId, PhpVersionFactory::MAX_PHP_VERSION);
			}

			return new PhpVersion($versionId);
		});
		if ($minVersion !== null) {
			$this->minVersion = new PhpVersion($minVersion->getVersionId());
		}
		if ($maxVersion === null) {
			return;
		}

		$this->maxVersion = $maxVersion;
	}

	public function getMinVersion(): ?PhpVersion
	{
		if ($this->initialized === false) {
			$this->initializeVersions();
		}

		return $this->minVersion;
	}

	public function getMaxVersion(): ?PhpVersion
	{
		if ($this->initialized === false) {
			$this->initializeVersions();
		}

		return $this->maxVersion;
	}

	/**
	 * Composer registers php-64bit as a virtual package carrying the very same version as php,
	 * so a requirement on either one constrains the PHP version, and requiring both means
	 * both constraints have to hold at once.
	 *
	 * @return list<string>
	 */
	private function getComposerRequireVersions(): array
	{
		$packageNames = $this->composerPhp64Bit ? ['php', 'php-64bit'] : ['php'];
		$composerPhpVersions = [];

		if (count($this->composerAutoloaderProjectPaths) > 0) {
			$composer = ComposerHelper::getComposerConfig(end($this->composerAutoloaderProjectPaths));
			if ($composer !== null) {
				foreach ($packageNames as $packageName) {
					$requiredVersion = $composer['require'][$packageName] ?? null;

					if (!is_string($requiredVersion)) {
						continue;
					}

					$composerPhpVersions[] = $requiredVersion;
				}
			}
		}

		return $composerPhpVersions;
	}

}
