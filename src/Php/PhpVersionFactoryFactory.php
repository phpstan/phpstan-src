<?php declare(strict_types = 1);

namespace PHPStan\Php;

use Nette\Utils\Json;
use PHPStan\File\FileReader;

class PhpVersionFactoryFactory
{

	private ?int $versionId;

	private bool $readComposerPhpVersion;

	/** @var string[] */
	private array $composerAutoloaderProjectPaths;

	/**
	 * @param bool $readComposerPhpVersion
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		?int $versionId,
		bool $readComposerPhpVersion,
		array $composerAutoloaderProjectPaths
	)
	{
		$this->versionId = $versionId;
		$this->readComposerPhpVersion = $readComposerPhpVersion;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
	}

	public function create(): PhpVersionFactory
	{
		$composerPhpVersion = null;
		if ($this->readComposerPhpVersion && count($this->composerAutoloaderProjectPaths) > 0) {
			$composerJsonPath = end($this->composerAutoloaderProjectPaths) . '/composer.json';
			if (is_file($composerJsonPath)) {
				try {
					$composerJsonContents = FileReader::read($composerJsonPath);
					$composer = Json::decode($composerJsonContents, Json::FORCE_ARRAY);
					$platformVersion = $composer['config']['platform']['php'] ?? null;
					if (is_string($platformVersion)) {
						$composerPhpVersion = $platformVersion;
					}
				} catch (\PHPStan\File\CouldNotReadFileException | \Nette\Utils\JsonException $e) {
					// pass
				}
			}
		}

		return new PhpVersionFactory($this->versionId, $composerPhpVersion);
	}

}
