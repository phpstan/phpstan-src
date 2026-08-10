<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\File\FileHelper;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use function array_key_exists;
use function is_array;
use function is_string;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Rewrites the absolute filesystem paths stored in the result cache to paths relative to an anchor
 * directory (and back), so the cache survives a change of the project's absolute path prefix
 * (a fresh CI checkout dir, a git worktree). See https://github.com/phpstan/phpstan/issues/8599
 *
 * Only paths under (or reachable from) the anchor become relative; a path with no shared prefix is
 * left absolute, following ccache's CCACHE_BASEDIR rule. absolutizePath() is the inverse: an already
 * absolute path is passed through unchanged, so relative and absolute entries can coexist in one cache.
 */
final class ResultCachePathTransformer
{

	private ParentDirectoryRelativePathHelper $relativePathHelper;

	private FileHelper $anchorFileHelper;

	public function __construct(string $anchorDirectory)
	{
		$this->relativePathHelper = new ParentDirectoryRelativePathHelper($anchorDirectory);
		$this->anchorFileHelper = new FileHelper($anchorDirectory);
	}

	public function relativizePath(string $path): string
	{
		[$scheme, $filesystemPath] = $this->splitScheme($path);
		if (!$this->isAbsolutePath($filesystemPath)) {
			return $path;
		}

		// Always store forward slashes so the cache is portable between Windows and Linux.
		// getRelativePath() already yields '/'-separated output for a path reachable from the anchor;
		// a path with no shared prefix is returned unchanged, so normalise its separators too.
		return $scheme . str_replace('\\', '/', $this->relativePathHelper->getRelativePath($filesystemPath));
	}

	public function absolutizePath(string $path): string
	{
		[$scheme, $filesystemPath] = $this->splitScheme($path);

		return $scheme . $this->anchorFileHelper->normalizePath($this->anchorFileHelper->absolutizePath($filesystemPath));
	}

	/**
	 * @param array<string, list<Error>> $errorsByFile
	 * @return array<string, list<Error>>
	 */
	public function relativizeErrors(array $errorsByFile): array
	{
		$result = [];
		foreach ($errorsByFile as $file => $errors) {
			$relativized = [];
			foreach ($errors as $error) {
				$relativized[] = $error->transformPaths(fn (string $path): string => $this->relativizePath($path));
			}
			$result[$this->relativizePath($file)] = $relativized;
		}

		return $result;
	}

	/**
	 * @param array<string, list<Error>> $errorsByFile
	 * @return array<string, list<Error>>
	 */
	public function absolutizeErrors(array $errorsByFile): array
	{
		$result = [];
		foreach ($errorsByFile as $file => $errors) {
			$absolutized = [];
			foreach ($errors as $error) {
				$absolutized[] = $error->transformPaths(fn (string $path): string => $this->absolutizePath($path));
			}
			$result[$this->absolutizePath($file)] = $absolutized;
		}

		return $result;
	}

	/**
	 * Rewrites only the top-level file-path keys, leaving the values untouched. Used for sections whose
	 * values carry no paths: collectedData, packageDependencies, exportedNodes, projectExtensionFiles.
	 *
	 * @param array<string, mixed> $byFile
	 * @return array<string, mixed>
	 */
	public function relativizeFileKeyed(array $byFile): array
	{
		$result = [];
		foreach ($byFile as $file => $value) {
			$result[$this->relativizePath($file)] = $value;
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $byFile
	 * @return array<string, mixed>
	 */
	public function absolutizeFileKeyed(array $byFile): array
	{
		$result = [];
		foreach ($byFile as $file => $value) {
			$result[$this->absolutizePath($file)] = $value;
		}

		return $result;
	}

	/**
	 * linesToIgnore/unmatchedLineIgnores: outer keys are plain file paths, inner keys are a file path
	 * OR a compound "path (in context of class X)"; leaf values carry no paths.
	 *
	 * @param array<string, mixed[]> $byFile
	 * @return array<string, mixed[]>
	 */
	public function relativizeCompoundKeyed(array $byFile): array
	{
		$result = [];
		foreach ($byFile as $file => $inner) {
			$relativizedInner = [];
			foreach ($inner as $innerKey => $value) {
				$relativizedInner[$this->relativizeCompoundKey((string) $innerKey)] = $value;
			}
			$result[$this->relativizePath($file)] = $relativizedInner;
		}

		return $result;
	}

	/**
	 * @param array<string, mixed[]> $byFile
	 * @return array<string, mixed[]>
	 */
	public function absolutizeCompoundKeyed(array $byFile): array
	{
		$result = [];
		foreach ($byFile as $file => $inner) {
			$absolutizedInner = [];
			foreach ($inner as $innerKey => $value) {
				$absolutizedInner[$this->absolutizeCompoundKey((string) $innerKey)] = $value;
			}
			$result[$this->absolutizePath($file)] = $absolutizedInner;
		}

		return $result;
	}

	/**
	 * @param array<string, array{fileHash: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}> $dependencies
	 * @return array<string, array{fileHash: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}>
	 */
	public function relativizeDependencies(array $dependencies): array
	{
		$result = [];
		foreach ($dependencies as $file => $data) {
			$data['dependentFiles'] = $this->relativizeList($data['dependentFiles']);
			if (array_key_exists('usedTraitDependentFiles', $data)) {
				$data['usedTraitDependentFiles'] = $this->relativizeList($data['usedTraitDependentFiles']);
			}
			$result[$this->relativizePath($file)] = $data;
		}

		return $result;
	}

	/**
	 * @param array<string, array{fileHash: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}> $dependencies
	 * @return array<string, array{fileHash: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}>
	 */
	public function absolutizeDependencies(array $dependencies): array
	{
		$result = [];
		foreach ($dependencies as $file => $data) {
			$data['dependentFiles'] = $this->absolutizeList($data['dependentFiles']);
			if (array_key_exists('usedTraitDependentFiles', $data)) {
				$data['usedTraitDependentFiles'] = $this->absolutizeList($data['usedTraitDependentFiles']);
			}
			$result[$this->absolutizePath($file)] = $data;
		}

		return $result;
	}

	/**
	 * Rewrites the absolute-path-bearing meta keys. projectConfig is handled separately by
	 * relativizeProjectConfig() because it is Neon-encoded to a string.
	 *
	 * @param mixed[] $meta
	 * @return mixed[]
	 */
	public function relativizeMeta(array $meta): array
	{
		return $this->transformMeta($meta, false);
	}

	/**
	 * @param mixed[] $meta
	 * @return mixed[]
	 */
	public function absolutizeMeta(array $meta): array
	{
		return $this->transformMeta($meta, true);
	}

	/**
	 * Only relativizes: projectConfig is stored as a relative Neon string and never absolutized on
	 * load. isMetaDifferent()/getMetaKeyDifferences() relativize the current config the same way to
	 * compare it against the cached string.
	 *
	 * @param mixed[] $projectConfig
	 * @return mixed[]
	 */
	public function relativizeProjectConfig(array $projectConfig): array
	{
		if (!array_key_exists('parameters', $projectConfig) || !is_array($projectConfig['parameters'])) {
			return $projectConfig;
		}

		$parameters = $projectConfig['parameters'];
		if (array_key_exists('paths', $parameters) && is_array($parameters['paths'])) {
			$parameters['paths'] = $this->relativizeList($parameters['paths']);
		}
		if (array_key_exists('tmpDir', $parameters) && is_string($parameters['tmpDir'])) {
			$parameters['tmpDir'] = $this->relativizePath($parameters['tmpDir']);
		}
		$projectConfig['parameters'] = $parameters;

		return $projectConfig;
	}

	/**
	 * @param mixed[] $meta
	 * @return mixed[]
	 */
	private function transformMeta(array $meta, bool $absolutize): array
	{
		if (array_key_exists('analysedPaths', $meta) && is_array($meta['analysedPaths'])) {
			$meta['analysedPaths'] = $this->transformList($meta['analysedPaths'], $absolutize);
		}

		foreach (['scannedFiles', 'composerLocks', 'executedFilesHashes', 'stubFiles'] as $key) {
			if (!array_key_exists($key, $meta) || !is_array($meta[$key])) {
				continue;
			}
			$meta[$key] = $this->transformKeys($meta[$key], $absolutize);
		}

		if (array_key_exists('composerInstalled', $meta) && is_array($meta['composerInstalled'])) {
			$meta['composerInstalled'] = $this->transformComposerInstalled($meta['composerInstalled'], $absolutize);
		}

		return $meta;
	}

	/**
	 * @param mixed[] $composerInstalled
	 * @return array<string, mixed>
	 */
	private function transformComposerInstalled(array $composerInstalled, bool $absolutize): array
	{
		$result = [];
		foreach ($composerInstalled as $file => $installed) {
			if (is_array($installed) && array_key_exists('versions', $installed) && is_array($installed['versions'])) {
				foreach ($installed['versions'] as $package => $packageData) {
					if (!is_array($packageData) || !array_key_exists('install_path', $packageData) || !is_string($packageData['install_path'])) {
						continue;
					}
					$installed['versions'][$package]['install_path'] = $this->transformPath($packageData['install_path'], $absolutize);
				}
			}
			$result[$this->transformPath((string) $file, $absolutize)] = $installed;
		}

		return $result;
	}

	private function transformPath(string $path, bool $absolutize): string
	{
		return $absolutize ? $this->absolutizePath($path) : $this->relativizePath($path);
	}

	/**
	 * @param mixed[] $paths
	 * @return list<string>
	 */
	private function transformList(array $paths, bool $absolutize): array
	{
		$result = [];
		foreach ($paths as $path) {
			$result[] = $this->transformPath((string) $path, $absolutize);
		}

		return $result;
	}

	/**
	 * @param mixed[] $paths
	 * @return list<string>
	 */
	private function relativizeList(array $paths): array
	{
		return $this->transformList($paths, false);
	}

	/**
	 * @param mixed[] $paths
	 * @return list<string>
	 */
	private function absolutizeList(array $paths): array
	{
		return $this->transformList($paths, true);
	}

	/**
	 * @param mixed[] $byKey
	 * @return array<string, mixed>
	 */
	private function transformKeys(array $byKey, bool $absolutize): array
	{
		$result = [];
		foreach ($byKey as $key => $value) {
			$result[$this->transformPath((string) $key, $absolutize)] = $value;
		}

		return $result;
	}

	private function relativizeCompoundKey(string $key): string
	{
		$suffixPosition = strpos($key, ' (in context of ');
		if ($suffixPosition === false) {
			return $this->relativizePath($key);
		}

		return $this->relativizePath(substr($key, 0, $suffixPosition)) . substr($key, $suffixPosition);
	}

	private function absolutizeCompoundKey(string $key): string
	{
		$suffixPosition = strpos($key, ' (in context of ');
		if ($suffixPosition === false) {
			return $this->absolutizePath($key);
		}

		return $this->absolutizePath(substr($key, 0, $suffixPosition)) . substr($key, $suffixPosition);
	}

	/**
	 * Splits a stream-wrapper URL into its scheme and the filesystem path that follows it. PHPStan
	 * ships the runtime stubs it registers as bootstrapFiles inside its own phar, so in a phar
	 * install those arrive here as `phar:///path/to/phpstan.phar/stubs/runtime/...`.
	 *
	 * Only the part after the scheme is rewritten, and the scheme is put back verbatim. Handing the
	 * whole URL to getRelativePath() drops the scheme, which absolutizePath() cannot reconstruct -
	 * the restored key then never equals the `phar://...` key the next run computes, so
	 * executedFilesHashes differs on every run and the cache is discarded every time.
	 *
	 * @return array{string, string} the scheme including `://` (empty when the path carries none),
	 *                               and the path following it
	 */
	private function splitScheme(string $path): array
	{
		if (preg_match('~^[a-z0-9+\-.]+://~i', $path, $matches) !== 1) {
			return ['', $path];
		}

		return [$matches[0], substr($path, strlen($matches[0]))];
	}

	private function isAbsolutePath(string $path): bool
	{
		if (DIRECTORY_SEPARATOR === '/') {
			return str_starts_with($path, '/');
		}

		return substr($path, 1, 1) === ':';
	}

}
