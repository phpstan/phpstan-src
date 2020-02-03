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

	private function makeDir(string $directory): void
	{
		$result = @mkdir($directory, 0777, true);
		if ($result === false && !is_dir($directory)) {
			throw new \InvalidArgumentException(sprintf('Directory "%s" doesn\'t exist.', $this->directory));
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
			$filePath = $this->getFilePath($key);
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
		$path = $this->getFilePath($key);
		$this->makeDir(dirname($path));

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
		if ($renameSuccess === false) {
			@unlink($tmpPath);
			throw new \InvalidArgumentException(sprintf('Could not write data to cache file %s.', $path));
		}
	}

	private function getFilePath(string $key): string
	{
		$keyHash = sha1($key);
		return sprintf(
			'%s/%s/%s/%s.php',
			$this->directory,
			substr($keyHash, 0, 2),
			substr($keyHash, 2, 2),
			$keyHash
		);
	}

}
