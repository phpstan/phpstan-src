<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;
use function explode;
use function get_include_path;
use function is_dir;
use function is_file;
use const PATH_SEPARATOR;

/**
 * Resolves a possibly-relative path the same way PHP would at runtime and checks whether it exists.
 *
 * We cannot use stream_resolve_include_path() because it works based on the calling script.
 * This mirrors its behavior but for an arbitrary script directory. The priority order is:
 * 	1. The base directories (e.g. from an attribute argument).
 * 	2. The current working directory.
 * 	3. The include path.
 * 	4. The directory of the script that is being analysed.
 */
#[AutowiredService]
final class FileExistenceChecker
{

	public function __construct(
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
	)
	{
	}

	/**
	 * @param list<string> $baseDirectories
	 */
	public function fileExists(string $path, string $scriptDirectory, array $baseDirectories = []): bool
	{
		return $this->exists($path, $scriptDirectory, $baseDirectories, false);
	}

	/**
	 * Like fileExists(), but a directory at the resolved path also counts as existing.
	 *
	 * @param list<string> $baseDirectories
	 */
	public function pathExists(string $path, string $scriptDirectory, array $baseDirectories = []): bool
	{
		return $this->exists($path, $scriptDirectory, $baseDirectories, true);
	}

	/**
	 * @param list<string> $baseDirectories
	 */
	private function exists(string $path, string $scriptDirectory, array $baseDirectories, bool $allowDirectory): bool
	{
		$scriptHelper = new FileHelper($scriptDirectory);
		$resolvedBaseDirectories = [];
		foreach ($baseDirectories as $baseDirectory) {
			// a relative base directory is resolved against the directory of the analysed script
			$resolvedBaseDirectories[] = $scriptHelper->absolutizePath($baseDirectory);
		}

		$directories = array_merge(
			$resolvedBaseDirectories,
			[$this->currentWorkingDirectory],
			explode(PATH_SEPARATOR, get_include_path()),
			[$scriptDirectory],
		);

		foreach ($directories as $directory) {
			$absolutePath = (new FileHelper($directory))->absolutizePath($path);

			if (is_file($absolutePath)) {
				return true;
			}

			if ($allowDirectory && is_dir($absolutePath)) {
				return true;
			}
		}

		return false;
	}

}
