<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function count;
use function end;
use function is_array;
use function is_file;
use function is_int;
use function is_string;

class PhpVersionFactoryFactory
{

	/**
	 * @param int|array{min: int, max: int}|null $phpVersion
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private int|array|null $phpVersion,
		private array $composerAutoloaderProjectPaths,
		private bool $bleedingEdge,
	)
	{
	}

	public function create(): PhpVersionFactory
	{
		$composerPhpVersion = null;
		if (count($this->composerAutoloaderProjectPaths) > 0) {
			$composerJsonPath = end($this->composerAutoloaderProjectPaths) . '/composer.json';
			if (is_file($composerJsonPath)) {
				try {
					$composerJsonContents = FileReader::read($composerJsonPath);
					$composer = Json::decode($composerJsonContents, Json::FORCE_ARRAY);
					$platformVersion = $composer['config']['platform']['php'] ?? null;
					if (is_string($platformVersion)) {
						$composerPhpVersion = $platformVersion;
					}
				} catch (CouldNotReadFileException | JsonException) {
					// pass
				}
			}
		}

		$versionId = null;

		if (is_int($this->phpVersion)) {
			$versionId = $this->phpVersion;
		}

		if ($this->bleedingEdge && is_array($this->phpVersion)) {
			if (!array_key_exists('min', $this->phpVersion)) {
				throw new ShouldNotHappenException();
			}
			$versionId = $this->phpVersion['min'];
		}

		return new PhpVersionFactory($versionId, $composerPhpVersion);
	}

}
