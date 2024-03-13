<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Composer\Semver\VersionParser;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use function count;
use function end;
use function is_file;
use function is_string;
use function sprintf;

class ComposerPhpVersionFactory
{

	private ?PhpVersion $minVersion = null;

	private ?PhpVersion $maxVersion = null;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
		?int $minVersion,
		?int $maxVersion,
	)
	{
		if ($minVersion !== null) {
			$this->minVersion = new PhpVersion($minVersion);
		}
		if ($maxVersion !== null) {
			$this->maxVersion = new PhpVersion($maxVersion);
		}

		if ($minVersion !== null && $maxVersion !== null) {
			return; // use values from config files
		}

		// fallback to composer.json based php-version constraint
		$composerPhpVersion = $this->getComposerRequireVersion();
		if ($composerPhpVersion === null) {
			return;
		}

		$parser = new VersionParser();
		$constraint = $parser->parseConstraints($composerPhpVersion);

		if ($this->minVersion === null && !$constraint->getLowerBound()->isZero()) {
			$this->minVersion = $this->buildVersion($constraint->getLowerBound()->getVersion());
		}
		if ($this->maxVersion === null && !$constraint->getUpperBound()->isPositiveInfinity()) {
			$this->maxVersion = $this->buildVersion($constraint->getUpperBound()->getVersion());
		}
	}

	public function getMinVersion(): ?PhpVersion
	{
		return $this->minVersion;
	}

	public function getMaxVersion(): ?PhpVersion
	{
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

		return new PhpVersion($versionId);
	}

}
