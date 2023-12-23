<?php declare(strict_types = 1);

namespace PHPStan\File;

use function count;
use function explode;
use function implode;
use function in_array;
use function ltrim;
use function realpath;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use const DIRECTORY_SEPARATOR;

class FuzzyRelativePathHelper implements RelativePathHelper
{

	private string $directorySeparator;

	private ?string $pathToTrim = null;

	/**
	 * @param string[] $analysedPaths
	 * @param non-empty-string|null $directorySeparator
	 */
	public function __construct(
		private RelativePathHelper $fallbackRelativePathHelper,
		string $currentWorkingDirectory,
		array $analysedPaths,
		?string $directorySeparator = null,
	)
	{
		if ($directorySeparator === null) {
			$directorySeparator = DIRECTORY_SEPARATOR;
		}

		$this->directorySeparator = $directorySeparator;
		$pathBeginning = null;
		$pathToTrimArray = null;
		$trimBeginning = static function (string $path): array {
			if (substr($path, 0, 1) === '/') {
				return [
					'/',
					substr($path, 1),
				];
			} elseif (substr($path, 1, 1) === ':') {
				return [
					substr($path, 0, 3),
					substr($path, 3),
				];
			}

			return ['', $path];
		};

		if (
			!in_array($currentWorkingDirectory, ['', '/'], true)
			&& !(strlen($currentWorkingDirectory) === 3 && substr($currentWorkingDirectory, 1, 1) === ':')
		) {
			[$pathBeginning, $currentWorkingDirectory] = $trimBeginning($currentWorkingDirectory);

			$pathToTrimArray = explode($directorySeparator, $currentWorkingDirectory);
		}
		foreach ($analysedPaths as $pathNumber => $path) {
			[$tempPathBeginning, $path] = $trimBeginning($path);

			$pathArray = explode($directorySeparator, $path);
			$pathTempParts = [];
			$pathArraySize = count($pathArray);
			foreach ($pathArray as $i => $pathPart) {
				if ($i === $pathArraySize - 1 && str_ends_with($pathPart, '.php')) {
					continue;
				}
				if (!isset($pathToTrimArray[$i])) {
					if ($pathNumber !== 0) {
						$pathToTrimArray = $pathTempParts;
						continue 2;
					}
				} elseif ($pathToTrimArray[$i] !== $pathPart) {
					$pathToTrimArray = $pathTempParts;
					continue 2;
				}

				$pathTempParts[] = $pathPart;
			}

			$pathBeginning = $tempPathBeginning;
			$pathToTrimArray = $pathTempParts;
		}

		if ($pathToTrimArray === null || count($pathToTrimArray) === 0) {
			return;
		}

		$pathToTrim = $pathBeginning . implode($directorySeparator, $pathToTrimArray);
		$realPathToTrim = realpath($pathToTrim);
		if ($realPathToTrim !== false) {
			$pathToTrim = $realPathToTrim;
		}

		$this->pathToTrim = $pathToTrim;
	}

	public function getRelativePath(string $filename): string
	{
		if (
			$this->pathToTrim !== null
			&& str_starts_with($filename, $this->pathToTrim)
		) {
			return ltrim(substr($filename, strlen($this->pathToTrim)), $this->directorySeparator);
		}

		return $this->fallbackRelativePathHelper->getRelativePath($filename);
	}

}
