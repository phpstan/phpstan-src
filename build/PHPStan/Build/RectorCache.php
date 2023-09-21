<?php declare(strict_types = 1);

namespace PHPStan\Build;

use Nette\Utils\Json;
use Nette\Utils\Strings;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RectorCache
{

	private const PATHS = [
		__DIR__ . '/../../../src',
		__DIR__ . '/../../../tests/PHPStan',
		__DIR__ . '/../../../tests/e2e',
	];

	private const CACHE_FILE = __DIR__ . '/../../../tmp/rectorCache.php';

	private const HASHES_FILE = __DIR__ . '/../../../tmp/rectorOriginalHashes.php';

	public const SKIP_PATHS = [
		'tests/*/data/*',
		'tests/*/Fixture/*',
		'tests/PHPStan/Analyser/traits/*',
		'tests/PHPStan/Generics/functions.php',
		'tests/e2e/resultCache_1.php',
		'tests/e2e/resultCache_2.php',
		'tests/e2e/resultCache_3.php',
	];

	/**
	 * @var string
	 * @see https://regex101.com/r/e1jm7v/1
	 */
	private const STARTS_WITH_ASTERISK_REGEX = '#^\\*(.*?)[^*]$#';
	/**
	 * @var string
	 * @see https://regex101.com/r/EgJQyZ/1
	 */
	private const ENDS_WITH_ASTERISK_REGEX = '#^[^*](.*?)\\*$#';

	/** @var null|array<int, string> */
	private static $restoreAlreadyRun = null;

	/**
	 * @return array<int, string>
	 */
	public function restore(): array
	{
		if (self::$restoreAlreadyRun !== null) {
			return self::$restoreAlreadyRun;
		}

		if (!is_file(self::CACHE_FILE)) {
			echo "Rector downgrade cache does not exist\n";
			return self::$restoreAlreadyRun = self::PATHS;
		}
		$cache = Json::decode(FileReader::read(self::CACHE_FILE), Json::FORCE_ARRAY);
		$files = $this->findFiles();
		$filesToDowngrade = [];
		foreach ($files as $file) {
			if (!isset($cache[$file])) {
				echo sprintf("File %s not found in cache - will be downgraded\n", $file);
				$filesToDowngrade[] = $file;
				continue;
			}

			$fileCache = $cache[$file];
			$hash = sha1_file($file);
			if ($hash === $fileCache['originalFileHash']) {
				FileWriter::write($file, $fileCache['downgradedContents']);
				continue;
			}

			echo sprintf("File %s has different hash - will be downgraded\n", $file);
			echo sprintf("%s vs. %s\n", $hash, $fileCache['originalFileHash']);

			$filesToDowngrade[] = $file;
		}

		if (count($filesToDowngrade) === 0) {
			echo "No new files to downgrade - done\n";
			exit(0);
		}

		return self::$restoreAlreadyRun = $filesToDowngrade;
	}

	public function saveHashes(): void
	{
		$files = $this->findFiles();
		$hashes = [];
		foreach ($files as $file) {
			$hashes[$file] = sha1_file($file);
		}

		FileWriter::write(self::HASHES_FILE, Json::encode($hashes));
	}

	public function getOriginalFilesHash(): string
	{
		$files = $this->findFiles();
		$hashes = [];
		foreach ($files as $file) {
			$hashes[] = $file . '~' . sha1_file($file);
		}

		return sha1(implode('-', $hashes));
	}

	/**
	 * @return array<int, string>
	 */
	private function findFiles(): array
	{
		$finder = new Finder();
		$finder->followLinks();
		$finder->filter(function (SplFileInfo $splFileInfo) : bool {
			$realPath = $splFileInfo->getRealPath();
			if ($realPath === '') {
				// dead symlink
				return \false;
			}
			// make the path work across different OSes
			$realPath = \str_replace('\\', '/', $realPath);
			// return false to remove file
			foreach (self::SKIP_PATHS as $excludePath) {
				// make the path work across different OSes
				$excludePath = \str_replace('\\', '/', $excludePath);
				if (Strings::match($realPath, '#' . \preg_quote($excludePath, '#') . '#') !== null) {
					return \false;
				}
				$excludePath = $this->normalizeForFnmatch($excludePath);
				if (\fnmatch($excludePath, $realPath)) {
					return \false;
				}
			}
			return \true;
		});
		$files = [];
		foreach ($finder->files()->name('*.php')->in(self::PATHS) as $fileInfo) {
			$files[] = $fileInfo->getRealPath();
		}

		return $files;
	}

	private function normalizeForFnmatch(string $path) : string
	{
		// ends with *
		if (Strings::match($path, self::ENDS_WITH_ASTERISK_REGEX) !== null) {
			return '*' . $path;
		}
		// starts with *
		if (Strings::match($path, self::STARTS_WITH_ASTERISK_REGEX) !== null) {
			return $path . '*';
		}
		return $path;
	}

	public function save(): void
	{
		$files = $this->findFiles();
		$originalHashes = Json::decode(FileReader::read(self::HASHES_FILE), Json::FORCE_ARRAY);
		$cache = [];
		foreach ($files as $file) {
			$cache[$file] = [
				'originalFileHash' => $originalHashes[$file],
				'downgradedContents' => FileReader::read($file),
			];
		}

		FileWriter::write(self::CACHE_FILE, Json::encode($cache));
	}

}
