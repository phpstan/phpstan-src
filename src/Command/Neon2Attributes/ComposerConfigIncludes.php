<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use Nette\FileNotFoundException;
use Nette\InvalidStateException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\DependencyInjection\LoaderFactory;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use function array_map;
use function dirname;
use function in_array;
use function is_array;
use function is_file;
use function is_string;

/**
 * Finds the config files of an extension that phpstan/extension-installer would include
 * for the projects that install it, listed in `extra.phpstan.includes` of composer.json.
 */
final class ComposerConfigIncludes
{

	/**
	 * @return list<string> the files not included by the project config file already
	 */
	public static function findMissing(string $directory, ?string $projectConfigFile): array
	{
		$fileHelper = new FileHelper($directory);
		try {
			$composer = Json::decode(FileReader::read($directory . '/composer.json'), Json::FORCE_ARRAY);
		} catch (CouldNotReadFileException | JsonException) {
			return [];
		}
		$includes = $composer['extra']['phpstan']['includes'] ?? null;
		if (!is_array($includes)) {
			return [];
		}

		$included = [];
		if ($projectConfigFile !== null) {
			$loader = (new LoaderFactory($fileHelper, dirname(__DIR__, 3), $directory, null, []))->createLoader();
			try {
				$loader->load($projectConfigFile, null);
			} catch (InvalidStateException | FileNotFoundException) {
				// reported when the container is created
				return [];
			}
			$included = array_map(static fn (string $file): string => $fileHelper->normalizePath($file), $loader->getDependencies());
		}

		$missing = [];
		foreach ($includes as $include) {
			if (!is_string($include)) {
				continue;
			}
			$file = $fileHelper->normalizePath($fileHelper->absolutizePath($include));
			if (!is_file($file) || in_array($file, $included, true)) {
				continue;
			}
			$missing[] = $file;
		}

		return $missing;
	}

}
