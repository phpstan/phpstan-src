<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function count;
use function hash;
use function intdiv;
use function is_array;
use function is_dir;
use function is_string;
use function ksort;
use function rtrim;
use function sort;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function usort;
use const PHP_VERSION_ID;

/**
 * Validates the merged `attributeServicesDirectories` section and computes each directory's
 * contribution to the container cache key.
 *
 * Runs on every PHPStan run, before the container compiles - the result decides whether
 * a cached container can be reused, so it cannot sit behind the container cache itself.
 * Directories owned by a regularly installed vendor package contribute their package's
 * version identity (no filesystem walk); directories of the project itself, and packages
 * installed from path repositories, contribute per-file content hashes.
 */
final class AttributeServicesDirectoriesResolver
{

	private ComposerProjectFactory $composerProjectFactory;

	/** @var list<ComposerProject>|null */
	private ?array $composerProjects = null;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private array $composerAutoloaderProjectPaths,
		private int $runtimePhpVersionId = PHP_VERSION_ID,
	)
	{
		$this->composerProjectFactory = new ComposerProjectFactory($fileHelper);
	}

	/**
	 * @param mixed $rawSectionValue
	 * @throws InvalidAttributeServicesDirectoriesException
	 */
	public function resolve($rawSectionValue): ResolvedAttributeServicesDirectories
	{
		if ($rawSectionValue === null || $rawSectionValue === []) {
			return ResolvedAttributeServicesDirectories::createEmpty();
		}

		if (!is_array($rawSectionValue)) {
			throw new InvalidAttributeServicesDirectoriesException([
				'The attributeServicesDirectories section must contain a list of directory paths.',
			]);
		}

		$errors = [];
		$directories = [];
		foreach ($rawSectionValue as $entry) {
			if (!is_string($entry)) {
				$errors[] = 'The attributeServicesDirectories section must contain a list of directory paths.';
				continue;
			}
			if (str_contains($entry, '%')) {
				$errors[] = sprintf('Entry %s in the attributeServicesDirectories section must be a plain path - %% parameters are not supported.', $entry);
				continue;
			}
			if (str_starts_with($entry, '*')) {
				$errors[] = sprintf('Entry %s in the attributeServicesDirectories section must be a plain path - wildcards are not supported.', $entry);
				continue;
			}

			$directories[] = rtrim($this->fileHelper->normalizePath($this->fileHelper->absolutizePath($entry), '/'), '/');
		}

		$directories = $this->deduplicateNested($directories);

		if (count($directories) > 0 && $this->runtimePhpVersionId < 80000) {
			$errors[] = sprintf(
				'The attributeServicesDirectories section requires PHP 8.0 or later, PHPStan is running on PHP %d.%d.%d.',
				intdiv($this->runtimePhpVersionId, 10000),
				intdiv($this->runtimePhpVersionId, 100) % 100,
				$this->runtimePhpVersionId % 100,
			);
			throw new InvalidAttributeServicesDirectoriesException($errors);
		}

		$resolved = [];
		foreach ($directories as $directory) {
			if (!is_dir($directory)) {
				$errors[] = sprintf('Directory %s from the attributeServicesDirectories section does not exist.', $directory);
				continue;
			}

			$resolved[] = $this->resolveDirectory($directory, $errors);
		}

		if (count($errors) > 0) {
			throw new InvalidAttributeServicesDirectoriesException($errors);
		}

		return new ResolvedAttributeServicesDirectories($resolved);
	}

	/**
	 * @param list<string> $errors
	 */
	private function resolveDirectory(string $directory, array &$errors): ResolvedAttributeServicesDirectory
	{
		$package = $this->findPackage($directory);
		if ($package !== null && $package->cacheToken !== null) {
			$cacheKeyComponent = [$directory => sprintf('package:%s:%s', $package->name, $package->cacheToken)];
		} else {
			$cacheKeyComponent = $this->hashDirectory($directory, $errors);
		}

		return new ResolvedAttributeServicesDirectory(
			$directory,
			$package?->name,
			$cacheKeyComponent,
		);
	}

	private function findPackage(string $directory): ?ComposerPackage
	{
		foreach ($this->getComposerProjects() as $project) {
			$package = $project->findPackageOfDirectory($directory);
			if ($package !== null) {
				return $package;
			}
		}

		return null;
	}

	/**
	 * @return list<ComposerProject>
	 */
	private function getComposerProjects(): array
	{
		if ($this->composerProjects !== null) {
			return $this->composerProjects;
		}

		$projects = [];
		foreach ($this->composerAutoloaderProjectPaths as $projectPath) {
			$project = $this->composerProjectFactory->create($projectPath);
			if ($project === null) {
				continue;
			}

			$projects[] = $project;
		}

		return $this->composerProjects = $projects;
	}

	/**
	 * Content hashes of the PHP files under the directory that can carry PHPStan's DI attributes -
	 * the cache-key fallback when a package version cannot stand in for the directory contents.
	 * Mirrors how config files invalidate the container in Configurator::getAllConfigFilesHashes().
	 *
	 * @param list<string> $errors
	 * @return array<string, string>
	 */
	private function hashDirectory(string $directory, array &$errors): array
	{
		$files = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
		);
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
				continue;
			}
			if (!str_ends_with($fileInfo->getFilename(), '.php')) {
				continue;
			}

			$files[] = $this->fileHelper->normalizePath($fileInfo->getPathname(), '/');
		}

		sort($files);

		$hashes = [];
		foreach ($files as $file) {
			try {
				$contents = FileReader::read($file);
			} catch (CouldNotReadFileException $e) {
				$errors[] = $e->getMessage();
				continue;
			}

			if (!AttributeServicesDiscoverer::mayAffectDiscovery($contents)) {
				continue;
			}

			$hashes[$file] = hash('sha256', $contents);
		}

		ksort($hashes);

		return $hashes;
	}

	/**
	 * Unique directories with entries nested inside another configured directory dropped -
	 * the outer directory already covers them for both discovery and hashing.
	 *
	 * @param list<string> $directories
	 * @return list<string>
	 */
	private function deduplicateNested(array $directories): array
	{
		usort($directories, static fn (string $a, string $b): int => strlen($a) <=> strlen($b));

		$result = [];
		foreach ($directories as $directory) {
			foreach ($result as $kept) {
				if ($directory === $kept || str_starts_with($directory, $kept . '/')) {
					continue 2;
				}
			}

			$result[] = $directory;
		}

		sort($result);

		return $result;
	}

}
