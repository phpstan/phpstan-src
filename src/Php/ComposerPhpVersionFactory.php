<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Composer\Semver\VersionParser;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use function count;
use function end;
use function is_array;
use function is_file;
use function is_int;
use function is_string;
use function max;
use function min;
use function sprintf;

final class ComposerPhpVersionFactory
{

	private ?PhpVersion $minVersion = null;

	private ?PhpVersion $maxVersion = null;

	private bool $initialized = false;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param int|array{min: int, max: int}|null $phpVersion
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
		private int|array|null $phpVersion,
	)
	{
	}

	private function initializeVersions(): void
	{
		$this->initialized = true;

		$phpVersion = $this->phpVersion;

		if (is_int($phpVersion)) {
			throw new ShouldNotHappenException();
		}

		if (is_array($phpVersion)) {
			if ($phpVersion['max'] < $phpVersion['min']) {
				throw new ShouldNotHappenException('Invalid PHP version range: phpVersion.max should be greater or equal to phpVersion.min.');
			}

			$this->minVersion = new PhpVersion($phpVersion['min']);
			$this->maxVersion = new PhpVersion($phpVersion['max']);

			return;
		}

		$this->minVersion = new PhpVersion(PhpVersionFactory::MIN_PHP_VERSION);
		$this->maxVersion = new PhpVersion(PhpVersionFactory::MAX_PHP_VERSION);

		// fallback to composer.json based php-version constraint
		$composerPhpVersion = $this->getComposerRequireVersion();
		if ($composerPhpVersion === null) {
			return;
		}

		$parser = new VersionParser();
		$constraint = $parser->parseConstraints($composerPhpVersion);

		if (!$constraint->getLowerBound()->isZero()) {
			$minVersion = $this->buildVersion($constraint->getLowerBound()->getVersion());

			if ($minVersion !== null) {
				$this->minVersion = new PhpVersion($minVersion->getVersionId());
			}
		}
		if ($constraint->getUpperBound()->isPositiveInfinity()) {
			return;
		}

		$this->maxVersion = $this->buildVersion($constraint->getUpperBound()->getVersion());
	}

	public function getMinVersion(): ?PhpVersion
	{
		if (is_int($this->phpVersion)) {
			return null;
		}

		if ($this->initialized === false) {
			$this->initializeVersions();
		}

		return $this->minVersion;
	}

	public function getMaxVersion(): ?PhpVersion
	{
		if (is_int($this->phpVersion)) {
			return null;
		}

		if ($this->initialized === false) {
			$this->initializeVersions();
		}

		return $this->maxVersion;
	}

	private function getComposerRequireVersion(): ?string
	{
		$composerPhpVersion = null;
		if (count($this->composerAutoloaderProjectPaths) > 0) {
			$composerJsonPath = end($this->composerAutoloaderProjectPaths) . '/composer.json';
			if (is_file($composerJsonPath)) {
				try {
					$composerJsonContents = FileReader::read($composerJsonPath);
					$composer = Json::decode($composerJsonContents, Json::FORCE_ARRAY);
					$requiredVersion = $composer['require']['php'] ?? null;
					if (is_string($requiredVersion)) {
						$composerPhpVersion = $requiredVersion;
					}
				} catch (CouldNotReadFileException | JsonException) {
					// pass
				}
			}
		}
		return $composerPhpVersion;
	}

	private function buildVersion(string $minVersion): ?PhpVersion
	{
		$matches = Strings::match($minVersion, '#^(\d+)\.(\d+)(?:\.(\d+))?#');
		if ($matches === null) {
			return null;
		}

		$major = $matches[1];
		$minor = $matches[2];
		$patch = $matches[3] ?? 0;
		$versionId = (int) sprintf('%d%02d%02d', $major, $minor, $patch);

		$versionId = max($versionId, PhpVersionFactory::MIN_PHP_VERSION);
		$versionId = min($versionId, PhpVersionFactory::MAX_PHP_VERSION);

		return new PhpVersion($versionId);
	}

}
