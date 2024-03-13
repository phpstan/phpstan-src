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

class ComposerMinPhpVersionFactory
{

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
	)
	{
	}

	public function create(): ?PhpVersion
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

		if ($composerPhpVersion === null) {
			return null;
		}

		$parser = new VersionParser();
		$constraint = $parser->parseConstraints($composerPhpVersion);
		$minVersion = $constraint->getLowerBound()->getVersion();

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
