<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use InvalidArgumentException;
use Nette\Utils\Random;
use PHPStan\File\FileWriter;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\ShouldNotHappenException;
use function error_get_last;
use function is_file;
use function rename;
use function sha1;
use function sprintf;
use function substr;
use function unlink;
use function var_export;
use const DIRECTORY_SEPARATOR;

class FileCacheStorage implements CacheStorage
{

	public function __construct(private string $directory)
	{
	}

	/**
	 * @throws \PHPStan\Internal\DirectoryCreatorException
	 */
	private function makeDir(string $directory): void
	{
		DirectoryCreator::ensureDirectoryExists($directory, 0777);
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
	 * @throws \PHPStan\Internal\DirectoryCreatorException
	 */
	public function save(string $key, string $variableKey, $data): void
	{
		[$firstDirectory, $secondDirectory, $path] = $this->getFilePaths($key);
		$this->makeDir($this->directory);
		$this->makeDir($firstDirectory);
		$this->makeDir($secondDirectory);

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
