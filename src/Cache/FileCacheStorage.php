<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use Nette\Utils\Random;

class FileCacheStorage implements CacheStorage
{

	/** @var string */
	private $directory;

	public function __construct(string $directory)
	{
		$this->directory = $directory;
	}

	public function makeRootDir(): void
	{
		$this->makeDir($this->directory);
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
			throw new \InvalidArgumentException(sprintf('Failed to create directory "%s" (%s).', $this->directory, $error !== null ? $error['message'] : 'unknown cause'));
		}
	}

	/**
	 * @param string $key
	 * @param string $variableKey
	 * @return mixed|null
	 */
	public function load(string $key, string $variableKey)
	{
		return (function (string $key, string $variableKey) {
			[,, $filePath] = $this->getFilePaths($key);
			if (!is_file($filePath)) {
				return null;
			}

			$cacheItem = require $filePath;
			if (!$cacheItem instanceof CacheItem) {
				return null;
			}
			if (!$cacheItem->isVariableKeyValid($variableKey)) {
				return null;
			}

			return $cacheItem->getData();
		})($key, $variableKey);
	}

	/**
	 * @param string $key
	 * @param string $variableKey
	 * @param mixed $data
	 * @return void
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		[$firstDirectory, $secondDirectory, $path] = $this->getFilePaths($key);
		$this->makeDir($firstDirectory);
		$this->makeDir($secondDirectory);

		$tmpPath = sprintf('%s/%s.tmp', $this->directory, Random::generate());
		$tmpSuccess = @file_put_contents(
			$tmpPath,
			sprintf(
				"<?php declare(strict_types = 1);\n\nreturn %s;",
				var_export(new CacheItem($variableKey, $data), true)
			)
		);
		if ($tmpSuccess === false) {
			throw new \InvalidArgumentException(sprintf('Could not write data to cache file %s.', $tmpPath));
		}

		$renameSuccess = @rename($tmpPath, $path);
		if ($renameSuccess) {
			return;
		}

		@unlink($tmpPath);
		if (DIRECTORY_SEPARATOR === '/' || !file_exists($path)) {
			throw new \InvalidArgumentException(sprintf('Could not write data to cache file %s.', $path));
		}
	}

	/**
	 * @param string $key
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
