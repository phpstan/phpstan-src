<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use InvalidArgumentException;
use Nette\Utils\Random;
use PHPStan\File\FileWriter;
use PHPStan\ShouldNotHappenException;
use Throwable;
use function clearstatcache;
use function error_get_last;
use function file_get_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function rename;
use function serialize;
use function sha1;
use function sprintf;
use function substr;
use function unlink;
use function unserialize;
use const DIRECTORY_SEPARATOR;

class FileCacheStorage implements CacheStorage
{

	public function __construct(private string $directory)
	{
	}

	private function makeDir(string $directory): void
	{
		if (is_dir($directory)) {
			return;
		}

		$result = @mkdir($directory, 0777);
		if ($result === false) {
			clearstatcache();
			if (is_dir($directory)) {
				return;
			}

			$error = error_get_last();
			throw new InvalidArgumentException(sprintf('Failed to create directory "%s" (%s).', $this->directory, $error !== null ? $error['message'] : 'unknown cause'));
		}
	}

	/**
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		[,, $filePath] = $this->getFilePaths($key);

		return (static function () use ($variableKey, $filePath) {
			if (!is_file($filePath)) {
				return null;
			}

			$data = file_get_contents($filePath);
			if ($data === false) {
				return null;
			}

			$cacheItem = @unserialize($data, ['allowed_classes' => false]);
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
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		[$firstDirectory, $secondDirectory, $path] = $this->getFilePaths($key);
		$this->makeDir($this->directory);
		$this->makeDir($firstDirectory);
		$this->makeDir($secondDirectory);

		$tmpPath = sprintf('%s/%s.tmp', $this->directory, Random::generate());
		try {
			$exported = serialize(new CacheItem($variableKey, $data));
		} catch (Throwable $t) {
			throw new ShouldNotHappenException(sprintf('Error occurred while saving item %s (%s) to cache: %s', $key, $variableKey, $t->getMessage()));
		}
		FileWriter::write($tmpPath, $exported);

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
	 * @return array{string, string, string}
	 */
	private function getFilePaths(string $key): array
	{
		$keyHash = sha1($key);
		$firstDirectory = sprintf('%s/%s', $this->directory, substr($keyHash, 0, 2));
		$secondDirectory = sprintf('%s/%s', $firstDirectory, substr($keyHash, 2, 2));
		$filePath = sprintf('%s/%s.php', $secondDirectory, $keyHash);

		return [
			$firstDirectory,
			$secondDirectory,
			$filePath,
		];
	}

}
