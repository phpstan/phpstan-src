<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use InvalidArgumentException;
use Nette\Utils\Random;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\CouldNotWriteFileException;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\ShouldNotHappenException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function array_keys;
use function closedir;
use function dirname;
use function error_get_last;
use function hash;
use function is_dir;
use function is_file;
use function opendir;
use function readdir;
use function rename;
use function rmdir;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function uksort;
use function unlink;
use function var_export;
use const DIRECTORY_SEPARATOR;

final class FileCacheStorage implements CacheStorage
{

	private const CACHED_CLEARED_VERSION = 'v2-new';

	public function __construct(private string $directory)
	{
	}

	/**
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		[,, $filePath] = $this->getFilePaths($key);

		return (static function () use ($variableKey, $filePath) {
			$cacheItem = @include $filePath;
			if (!$cacheItem instanceof CacheItem) {
				return null;
			}
			if (!$cacheItem->isVariableKeyValid($variableKey)) {
				return null;
			}

			return $cacheItem->getData();
		})();
	}

	/**
	 * @param mixed $data
	 * @throws DirectoryCreatorException
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		[$firstDirectory, $secondDirectory, $path] = $this->getFilePaths($key);
		DirectoryCreator::ensureDirectoryExists($this->directory, 0777);
		DirectoryCreator::ensureDirectoryExists($firstDirectory, 0777);
		DirectoryCreator::ensureDirectoryExists($secondDirectory, 0777);

		$tmpPath = sprintf('%s/%s.tmp', $this->directory, Random::generate());
		$errorBefore = error_get_last();
		$exported = @var_export(new CacheItem($variableKey, $data), true);
		$errorAfter = error_get_last();
		if ($errorAfter !== null && $errorBefore !== $errorAfter) {
			throw new ShouldNotHappenException(sprintf('Error occurred while saving item %s (%s) to cache: %s', $key, $variableKey, $errorAfter['message']));
		}
		FileWriter::write(
			$tmpPath,
			sprintf(
				"<?php declare(strict_types = 1);\n\n%s\nreturn %s;",
				sprintf('// %s', $key),
				$exported,
			),
		);

		$renameSuccess = @rename($tmpPath, $path);
		if ($renameSuccess) {
			return;
		}

		@unlink($tmpPath);
		if (DIRECTORY_SEPARATOR === '/' || !is_file($path)) {
			throw new InvalidArgumentException(sprintf('Could not write data to cache file %s.', $path));
		}
	}

	/**
	 * @param non-empty-string $key
	 *
	 * @return array{string, string, string}
	 */
	private function getFilePaths(string $key): array
	{
		$keyHash = hash('sha256', $key);
		$firstDirectory = sprintf('%s/%s', $this->directory, substr($keyHash, 0, 2));
		$secondDirectory = sprintf('%s/%s', $firstDirectory, substr($keyHash, 2, 2));
		$filePath = sprintf('%s/%s.php', $secondDirectory, $keyHash);

		return [
			$firstDirectory,
			$secondDirectory,
			$filePath,
		];
	}

	public function clearUnusedFiles(): void
	{
		if (!is_dir($this->directory)) {
			return;
		}

		$cachedClearedFile = $this->directory . '/cache-cleared';
		if (is_file($cachedClearedFile)) {
			try {
				$cachedClearedContents = FileReader::read($cachedClearedFile);
				if ($cachedClearedContents === self::CACHED_CLEARED_VERSION) {
					return;
				}
			} catch (CouldNotReadFileException) {
				return;
			}
		}

		$iterator = new RecursiveDirectoryIterator($this->directory);
		$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($iterator);
		$beginFunction = sprintf(
			"<?php declare(strict_types = 1);\n\n%s",
			sprintf('// %s', 'variadic-function-'),
		);
		$beginMethod = sprintf(
			"<?php declare(strict_types = 1);\n\n%s",
			sprintf('// %s', 'variadic-method-'),
		);
		$beginNew = "<?php declare(strict_types = 1);\n\n//";
		$emptyDirectoriesToCheck = [];
		foreach ($files as $file) {
			try {
				$path = $file->getPathname();
				$contents = FileReader::read($path);
				if (
					!str_starts_with($contents, $beginFunction)
					&& !str_starts_with($contents, $beginMethod)
					&& str_starts_with($contents, $beginNew)
				) {
					continue;
				}

				$emptyDirectoriesToCheck[dirname($path)] = true;
				$emptyDirectoriesToCheck[dirname($path, 2)] = true;

				@unlink($path);
			} catch (CouldNotReadFileException) {
				continue;
			}
		}

		uksort($emptyDirectoriesToCheck, static fn ($a, $b) => strlen($b) - strlen($a));

		foreach (array_keys($emptyDirectoriesToCheck) as $directory) {
			if (!$this->isDirectoryEmpty($directory)) {
				continue;
			}

			@rmdir($directory);
		}

		try {
			FileWriter::write($cachedClearedFile, self::CACHED_CLEARED_VERSION);
		} catch (CouldNotWriteFileException) {
			// pass
		}
	}

	private function isDirectoryEmpty(string $directory): bool
	{
		$handle = opendir($directory);
		if ($handle === false) {
			return false;
		}
		while (($entry = readdir($handle)) !== false) {
			if ($entry !== '.' && $entry !== '..') {
				closedir($handle);
				return false;
			}
		}

		closedir($handle);
		return true;
	}

}
