<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function count;
use function hash_file;
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
 * Validates the merged `attributeServicesDirectories` section against the analysed project's
 * Composer metadata and computes each directory's contribution to the container cache key.
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

			$resolvedDirectory = $this->resolveDirectory($directory, $errors);
			if ($resolvedDirectory === null) {
				continue;
			}

			$resolved[] = $resolvedDirectory;
		}

		if (count($errors) > 0) {
			throw new InvalidAttributeServicesDirectoriesException($errors);
		}

		return new ResolvedAttributeServicesDirectories($resolved);
	}

	/**
	 * @param list<string> $errors
	 */
	private function resolveDirectory(string $directory, array &$errors): ?ResolvedAttributeServicesDirectory
	{
		$ownership = $this->findOwnership($directory);
		if ($ownership === null) {
			$errors[] = sprintf('Directory %s from the attributeServicesDirectories section is not inside any project with Composer metadata known to PHPStan.', $directory);
			return null;
		}

		[$project, $package] = $ownership;

		if ($package !== null) {
			// autoload-dev of a dependency is never installed by Composer
			$rules = $package->autoload;
		} elseif ($project->devInstalled) {
			$rules = $project->rootAutoload->union($project->rootAutoloadDev);
		} else {
			$rules = $project->rootAutoload;
		}

		$psr4 = [];
		foreach ($rules->psr4 as $namespacePrefix => $baseDirectories) {
			foreach ($baseDirectories as $baseDirectory) {
				if (!$this->pathsIntersect($directory, $baseDirectory)) {
					continue;
				}

				$psr4[$namespacePrefix][] = $baseDirectory;
			}
		}

		$classmapPaths = [];
		foreach ($rules->classmapPaths as $classmapPath) {
			if (!$this->pathsIntersect($directory, $classmapPath)) {
				continue;
			}

			$classmapPaths[] = $classmapPath;
		}

		if (count($psr4) === 0 && count($classmapPaths) === 0) {
			$errors[] = $this->describeUncoveredDirectory($directory, $project, $package);
			return null;
		}

		if ($package !== null && $package->cacheToken !== null) {
			$cacheKeyComponent = [$directory => sprintf('package:%s:%s', $package->name, $package->cacheToken)];
		} else {
			$cacheKeyComponent = $this->hashDirectory($directory, $errors);
		}

		return new ResolvedAttributeServicesDirectory(
			$directory,
			$package === null ? null : $package->name,
			$psr4,
			$classmapPaths,
			$project->getAutoloadClassmapPath(),
			$cacheKeyComponent,
		);
	}

	private function describeUncoveredDirectory(string $directory, ComposerProject $project, ?ComposerPackage $package): string
	{
		$subject = $package === null
			? sprintf('the autoload section of %s/composer.json', $project->rootPath)
			: sprintf('the autoload section of the Composer package %s', $package->name);

		if ($package === null) {
			foreach ($this->collectPsrAndFilePaths($project->rootAutoloadDev) as $path) {
				if (!$this->pathsIntersect($directory, $path)) {
					continue;
				}

				if (!$project->devInstalled) {
					return sprintf(
						'Directory %s from the attributeServicesDirectories section is only covered by the autoload-dev section of %s/composer.json but Composer dependencies were installed with --no-dev.',
						$directory,
						$project->rootPath,
					);
				}
			}
		}

		$unsupportedRules = $package === null
			? ($project->devInstalled ? $project->rootAutoload->union($project->rootAutoloadDev) : $project->rootAutoload)
			: $package->autoload;
		foreach ($unsupportedRules->psr0 as $baseDirectories) {
			foreach ($baseDirectories as $baseDirectory) {
				if (!$this->pathsIntersect($directory, $baseDirectory)) {
					continue;
				}

				return sprintf(
					'Directory %s from the attributeServicesDirectories section is only covered by a psr-0 autoload rule of %s. Only psr-4 and classmap rules are supported.',
					$directory,
					$subject,
				);
			}
		}

		return sprintf(
			'Directory %s from the attributeServicesDirectories section is not covered by %s. Add it to autoload.psr-4 or autoload.classmap in composer.json and run composer dump-autoload.',
			$directory,
			$subject,
		);
	}

	/**
	 * @return array{ComposerProject, ComposerPackage|null}|null
	 */
	private function findOwnership(string $directory): ?array
	{
		foreach ($this->getComposerProjects() as $project) {
			$package = $project->findPackageOfDirectory($directory);
			if ($package !== null) {
				return [$project, $package];
			}
		}

		$containing = null;
		foreach ($this->getComposerProjects() as $project) {
			if (!$project->containsDirectory($directory)) {
				continue;
			}

			if ($containing !== null && strlen($containing->rootPath) >= strlen($project->rootPath)) {
				continue;
			}

			$containing = $project;
		}

		if ($containing === null) {
			return null;
		}

		return [$containing, null];
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
	 * @return list<string>
	 */
	private function collectPsrAndFilePaths(AutoloadRules $rules): array
	{
		$paths = [];
		foreach ($rules->psr4 as $baseDirectories) {
			foreach ($baseDirectories as $baseDirectory) {
				$paths[] = $baseDirectory;
			}
		}
		foreach ($rules->classmapPaths as $path) {
			$paths[] = $path;
		}

		return $paths;
	}

	private function pathsIntersect(string $a, string $b): bool
	{
		return $a === $b || str_starts_with($a, $b . '/') || str_starts_with($b, $a . '/');
	}

	/**
	 * Content hashes of all PHP files under the directory - the cache-key fallback when
	 * a package version cannot stand in for the directory contents. Mirrors how config
	 * files invalidate the container in Configurator::getAllConfigFilesHashes().
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
			$hash = hash_file('sha256', $file);
			if ($hash === false) {
				$errors[] = (new CouldNotReadFileException($file))->getMessage();
				continue;
			}

			$hashes[$file] = $hash;
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
