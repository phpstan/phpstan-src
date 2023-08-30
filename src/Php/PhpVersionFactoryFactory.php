<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use function count;
use function end;
use function is_string;

class PhpVersionFactoryFactory
{

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private ?int $versionId,
		private array $composerAutoloaderProjectPaths,
	)
	{
	}

	public function create(): PhpVersionFactory
	{
		$composerPhpVersion = null;
		if (count($this->composerAutoloaderProjectPaths) > 0) {
			$composerJsonPath = end($this->composerAutoloaderProjectPaths) . '/composer.json';
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

		return new PhpVersionFactory($this->versionId, $composerPhpVersion);
	}

}
