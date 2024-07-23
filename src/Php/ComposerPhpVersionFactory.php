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
use function sprintf;

class ComposerPhpVersionFactory
{

	private ?PhpVersion $minVersion = null;

	private ?PhpVersion $maxVersion = null;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param int|array{min: int, max: int}|null $phpVersion
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
		int|array|null $phpVersion,
		bool $bleedingEdge,
	)
	{
		if (is_int($phpVersion)) {
			return;
		}

		if (is_array($phpVersion)) {
			if ($phpVersion['max'] < $phpVersion['min']) {
				throw new ShouldNotHappenException('Invalid PHP version range: phpVersion.max should be greater or equal to phpVersion.min.');
			}

			$this->minVersion = new PhpVersion($phpVersion['min']);
			$this->maxVersion = new PhpVersion($phpVersion['max']);

			return;
		}

		if (!$bleedingEdge) {
			return;
		}

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
