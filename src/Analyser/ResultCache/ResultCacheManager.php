<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Nette\Neon\Neon;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Command\Output;
use PHPStan\Dependency\ExportedNode\ExportedTraitNode;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\ProjectConfigHelper;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\CouldNotWriteFileException;
use PHPStan\File\FileFinder;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ArrayHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use ReflectionException;
use Throwable;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function error_get_last;
use function explode;
use function fclose;
use function fopen;
use function fwrite;
use function get_loaded_extensions;
use function hash_file;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use function ksort;
use function microtime;
use function sort;
use function sprintf;
use function str_starts_with;
use function substr;
use function time;
use function unlink;
use function var_export;
use const PHP_VERSION_ID;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 * @phpstan-import-type CollectorData from CollectedData
 */
#[GenerateFactory(interface: ResultCacheManagerFactory::class)]
final class ResultCacheManager
{

	/**
	 * Loaded PHP extensions that cannot change the analysis result, so switching them on or off
	 * must not invalidate the whole cache. Profilers do not take part in the analysis at all, and
	 * phpstan_turbo only replaces PHPStan's own classes with native implementations that behave
	 * identically, so it cannot change what the cache holds. Keeping it here would make every
	 * cache built where the extension is available useless everywhere it is not: Windows, a PHP
	 * version with no distributed binary, or a libc the binaries are not built for.
	 */
	private const EXTENSIONS_NOT_INVALIDATING_CACHE = ['xdebug', 'blackfire', 'phpstan_turbo'];

	private const CACHE_VERSION = 'v14-relativePaths';

	/** @var array<string, string> */
	private array $fileHashes = [];

	private ?ResultCachePathTransformer $pathTransformer = null;

	/** @var array<string, true> */
	private array $alreadyProcessed = [];

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $bootstrapFiles
	 * @param string[] $scanFiles
	 * @param string[] $scanDirectories
	 * @param string[] $configStubFiles
	 * @param list<string|non-empty-list<string>> $parametersNotInvalidatingCache
	 * @param array<string, string> $fileReplacements
	 * @param ExtensionsCollection<ResultCacheMetaExtension> $resultCacheMetaExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: ResultCacheMetaExtension::class)]
		private ExtensionsCollection $resultCacheMetaExtensions,
		private ExportedNodeFetcher $exportedNodeFetcher,
		#[AutowiredParameter(ref: '@fileFinderScan')]
		private FileFinder $scanFileFinder,
		private StubFilesProvider $stubFilesProvider,
		private FileHelper $fileHelper,
		private PackageDependencyResolver $packageDependencyResolver,
		#[AutowiredParameter(ref: '%resultCachePath%')]
		private string $cacheFilePath,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private string $usedLevel,
		#[AutowiredParameter]
		private ?string $cliAutoloadFile,
		#[AutowiredParameter]
		private array $bootstrapFiles,
		#[AutowiredParameter]
		private array $scanFiles,
		#[AutowiredParameter]
		private array $scanDirectories,
		#[AutowiredParameter(ref: '%stubFiles%')]
		private array $configStubFiles,
		private array $fileReplacements,
		#[AutowiredParameter(ref: '%resultCacheChecksProjectExtensionFilesDependencies%')]
		private bool $checkDependenciesOfProjectExtensionFiles,
		#[AutowiredParameter]
		private array $parametersNotInvalidatingCache,
		#[AutowiredParameter(ref: '%resultCacheSkipIfOlderThanDays%')]
		private int $skipResultCacheIfOlderThanDays,
		#[AutowiredParameter(ref: '%rootDir%')]
		private string $anchorDirectory,
	)
	{
	}

	private function getPathTransformer(): ResultCachePathTransformer
	{
		return $this->pathTransformer ??= new ResultCachePathTransformer($this->anchorDirectory);
	}

	/**
	 * Whether the result cache file was present on disk when PHPStan started.
	 * Distinguishes "the cache never existed" from "the cache existed but was invalid".
	 */
	public function resultCacheExists(): bool
	{
		return is_file($this->cacheFilePath);
	}

	/**
	 * Builds the "the whole project has to be reanalysed" result, recording the reason
	 * why the cache could not be used. The reason is both printed in very verbose mode
	 * and kept on the ResultCache so the result-cache-info command can report it.
	 *
	 * @param string[] $allAnalysedFiles
	 * @param mixed[] $meta
	 * @param array<string, string> $currentFileHashes
	 */
	private function fullAnalysis(
		string $reason,
		array $allAnalysedFiles,
		array $meta,
		array $currentFileHashes,
		Output $output,
	): ResultCache
	{
		if ($output->isVeryVerbose()) {
			$output->writeLineFormatted($reason);
		}

		return new ResultCache(
			filesToAnalyse: $allAnalysedFiles,
			fullAnalysis: true,
			fullAnalysisReason: $reason,
			lastFullAnalysisTime: time(),
			meta: $meta,
			errors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			collectedData: [],
			dependencies: [],
			usedTraitDependencies: [],
			packageDependencies: [],
			exportedNodes: [],
			projectExtensionFiles: [],
			currentFileHashes: $currentFileHashes,
		);
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param mixed[]|null $projectConfigArray
	 */
	public function restore(array $allAnalysedFiles, bool $debug, bool $onlyFiles, ?array $projectConfigArray, Output $output): ResultCache
	{
		$startTime = microtime(true);
		$currentFileHashes = [];
		foreach ($allAnalysedFiles as $analysedFile) {
			if (!is_file($analysedFile)) {
				continue;
			}
			$currentFileHashes[$analysedFile] = $this->getFileHash($analysedFile);
		}
		if ($debug) {
			return $this->fullAnalysis(
				'Result cache not used because of debug mode.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$currentFileHashes,
				$output,
			);
		}
		if ($onlyFiles) {
			return $this->fullAnalysis(
				'Result cache not used because only files were passed as analysed paths.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$currentFileHashes,
				$output,
			);
		}

		$cacheFilePath = $this->cacheFilePath;
		if (!is_file($cacheFilePath)) {
			return $this->fullAnalysis(
				'Result cache not used because the cache file does not exist.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$currentFileHashes,
				$output,
			);
		}

		try {
			$data = require $cacheFilePath;
		} catch (Throwable $e) {
			@unlink($cacheFilePath);

			return $this->fullAnalysis(
				sprintf('Result cache not used because an error occurred while loading the cache file: %s', $e->getMessage()),
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$currentFileHashes,
				$output,
			);
		}

		if (!is_array($data)) {
			@unlink($cacheFilePath);

			return $this->fullAnalysis(
				'Result cache not used because the cache file is corrupted.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$currentFileHashes,
				$output,
			);
		}

		// The cache stores paths relative to the anchor directory. Re-absolutize them against the current
		// anchor before anything reads them, so a moved project (a fresh CI checkout dir, a git worktree)
		// resolves to its new location. projectConfig stays a relative Neon string here;
		// isMetaDifferent()/getMetaKeyDifferences() relativize the current side to compare. Absolutizing an
		// already-absolute path is a no-op, so a cache from an older format is left untouched (and then
		// discarded by the cacheVersion check below).
		$transformer = $this->getPathTransformer();
		$data['meta'] = $transformer->absolutizeMeta($data['meta']);
		$data['projectExtensionFiles'] = $transformer->absolutizeFileKeyed($data['projectExtensionFiles']);
		$data['linesToIgnore'] = $transformer->absolutizeCompoundKeyed($data['linesToIgnore']);
		$data['unmatchedLineIgnores'] = $transformer->absolutizeCompoundKeyed($data['unmatchedLineIgnores']);
		$data['dependencies'] = $transformer->absolutizeDependencies($data['dependencies']);
		$data['packageDependencies'] = $transformer->absolutizeFileKeyed($data['packageDependencies'] ?? []);

		$errorsCallback = $data['errorsCallback'];
		$data['errorsCallback'] = static fn (): array => $transformer->absolutizeErrors($errorsCallback());
		$locallyIgnoredErrorsCallback = $data['locallyIgnoredErrorsCallback'];
		$data['locallyIgnoredErrorsCallback'] = static fn (): array => $transformer->absolutizeErrors($locallyIgnoredErrorsCallback());
		$collectedDataCallback = $data['collectedDataCallback'];
		$data['collectedDataCallback'] = static fn (): array => $transformer->absolutizeCollectedData($collectedDataCallback());
		$exportedNodesCallback = $data['exportedNodesCallback'];
		$data['exportedNodesCallback'] = static fn (): array => $transformer->absolutizeFileKeyed($exportedNodesCallback());

		// The stub file hashes get into the meta only at save time, after the analysis - the
		// only point where the StubFilesExtensions may run, because they can rely on
		// bootstrapFiles having been executed. Restoring must not run them, so the entry is
		// taken out of the cached meta here; the freshly computed meta below never contains it,
		// and isMetaDifferent() never sees it on either side. Instead, the recorded hashes are
		// verified after the meta comparison - a changed stub file invalidates the whole cache.
		// A different list coming from an extension is not detected - projectExtensionFiles
		// cover that.
		$cachedStubFiles = [];
		if (array_key_exists('stubFiles', $data['meta']) && is_array($data['meta']['stubFiles'])) {
			/** @var array<string, string> $cachedStubFiles */
			$cachedStubFiles = $data['meta']['stubFiles'];
			unset($data['meta']['stubFiles']);
		}

		$meta = $this->getMeta($allAnalysedFiles, $projectConfigArray);
		// absolutized above, so it is always present here
		$packageDependencies = $data['packageDependencies'];
		$packageSeededFiles = [];
		if ($this->isMetaDifferent($data['meta'], $meta)) {
			$diffs = $this->getMetaKeyDifferences($data['meta'], $meta);

			// If the metadata differ ONLY in the Composer lock/installed files, the generated container
			// and analysis are unchanged except for code coming from packages whose version actually
			// changed. Re-analyse just the files depending on a changed package instead of everything;
			// the existing incremental loop below then propagates to their dependents on signature change.
			// Any other meta difference, or an undetermined change set (installed.php cannot be parsed),
			// falls back to a full re-analysis.
			$changedPackages = array_diff($diffs, ['composerLocks', 'composerInstalled']) === []
				? $this->packageDependencyResolver->getChangedComposerPackages($data['meta'], $meta)
				: null;

			if ($changedPackages === null) {
				return $this->fullAnalysis(
					'Result cache not used because the metadata do not match: ' . implode(', ', $diffs),
					$allAnalysedFiles,
					$meta,
					$currentFileHashes,
					$output,
				);
			}

			if ($changedPackages === []) {
				// The Composer lock/installed metadata changed but no installed package's version or
				// reference did (e.g. a composer.lock regenerated with different formatting or dist/time
				// metadata, common in CI where composer.lock is not committed). Nothing analysis-relevant
				// changed, so keep the restored cache and fall through to the normal incremental analysis
				// instead of re-analysing everything.
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Composer metadata changed but no package versions changed; keeping the result cache.');
				}
			} else {
				$changedPackagesLookup = array_fill_keys($changedPackages, true);
				if ($this->changedPackagesProvideContainerClass($projectConfigArray, $changedPackagesLookup)) {
					// One of the changed packages registers a class in the PHPStan container (a rule,
					// extension, and so on). Such code can affect the analysis of every file, not just the
					// files that reference it, so the file-granular re-seed below is not enough - re-analyse
					// everything.
					return $this->fullAnalysis(
						sprintf(
							'Composer packages changed (%s) and register a class in the container; re-analysing everything.',
							implode(', ', $changedPackages),
						),
						$allAnalysedFiles,
						$meta,
						$currentFileHashes,
						$output,
					);
				}

				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted(sprintf(
						'Composer packages changed (%s); re-analysing only the files depending on them.',
						implode(', ', $changedPackages),
					));
				}
				foreach ($packageDependencies as $packageDependentFile => $filePackages) {
					foreach ($filePackages as $filePackage) {
						if (isset($changedPackagesLookup[$filePackage])) {
							$packageSeededFiles[] = $packageDependentFile;
							break;
						}
					}
				}
			}
		}

		$daysOldForSkip = $this->skipResultCacheIfOlderThanDays;
		if (time() - $data['lastFullAnalysisTime'] >= 60 * 60 * 24 * $daysOldForSkip) {
			return $this->fullAnalysis(
				sprintf("Result cache not used because it's more than %d days since last full analysis.", $daysOldForSkip),
				$allAnalysedFiles,
				$meta,
				$currentFileHashes,
				$output,
			);
		}

		/**
		 * @var string $fileHash
		 * @var bool $isAnalysed
		 */
		foreach ($data['projectExtensionFiles'] as $extensionFile => [$fileHash, $isAnalysed]) {
			if (!$isAnalysed) {
				continue;
			}
			if (!is_file($extensionFile)) {
				return $this->fullAnalysis(
					sprintf('Result cache not used because extension file %s was not found.', $extensionFile),
					$allAnalysedFiles,
					$meta,
					$currentFileHashes,
					$output,
				);
			}

			if ($this->getFileHash($extensionFile) === $fileHash) {
				continue;
			}

			return $this->fullAnalysis(
				sprintf('Result cache not used because extension file %s hash does not match.', $extensionFile),
				$allAnalysedFiles,
				$meta,
				$currentFileHashes,
				$output,
			);
		}

		foreach ($cachedStubFiles as $stubFile => $stubFileHash) {
			if (!is_file($stubFile)) {
				return $this->fullAnalysis(
					sprintf('Result cache not used because stub file %s was not found.', $stubFile),
					$allAnalysedFiles,
					$meta,
					$currentFileHashes,
					$output,
				);
			}

			if ($this->getFileHash($stubFile) === $stubFileHash) {
				continue;
			}

			return $this->fullAnalysis(
				sprintf('Result cache not used because stub file %s hash does not match.', $stubFile),
				$allAnalysedFiles,
				$meta,
				$currentFileHashes,
				$output,
			);
		}

		$invertedDependencies = $data['dependencies'];
		$deletedFiles = array_fill_keys(array_keys($invertedDependencies), true);
		$filesToAnalyse = [];
		$invertedDependenciesToReturn = [];
		$invertedUsedTraitDependenciesToReturn = [];
		$errors = $data['errorsCallback']();
		$locallyIgnoredErrors = $data['locallyIgnoredErrorsCallback']();
		$linesToIgnore = $data['linesToIgnore'];
		$unmatchedLineIgnores = $data['unmatchedLineIgnores'];
		$collectedData = $data['collectedDataCallback']();
		$exportedNodes = $data['exportedNodesCallback']();
		$filteredErrors = [];
		$filteredLocallyIgnoredErrors = [];
		$filteredLinesToIgnore = [];
		$filteredUnmatchedLineIgnores = [];
		$filteredCollectedData = [];
		$filteredExportedNodes = [];
		$newFileAppeared = false;

		foreach (array_keys($cachedStubFiles) as $stubFile) {
			if (!array_key_exists($stubFile, $errors)) {
				continue;
			}

			$filteredErrors[$stubFile] = $errors[$stubFile];
		}

		foreach ($allAnalysedFiles as $analysedFile) {
			if (array_key_exists($analysedFile, $errors)) {
				$filteredErrors[$analysedFile] = $errors[$analysedFile];
			}
			if (array_key_exists($analysedFile, $locallyIgnoredErrors)) {
				$filteredLocallyIgnoredErrors[$analysedFile] = $locallyIgnoredErrors[$analysedFile];
			}
			if (array_key_exists($analysedFile, $linesToIgnore)) {
				$filteredLinesToIgnore[$analysedFile] = $linesToIgnore[$analysedFile];
			}
			if (array_key_exists($analysedFile, $unmatchedLineIgnores)) {
				$filteredUnmatchedLineIgnores[$analysedFile] = $unmatchedLineIgnores[$analysedFile];
			}
			if (array_key_exists($analysedFile, $collectedData)) {
				$filteredCollectedData[$analysedFile] = $collectedData[$analysedFile];
			}
			if (array_key_exists($analysedFile, $exportedNodes)) {
				$filteredExportedNodes[$analysedFile] = $exportedNodes[$analysedFile];
			}
			if (!array_key_exists($analysedFile, $invertedDependencies)) {
				// new file
				$filesToAnalyse[] = $analysedFile;
				$newFileAppeared = true;
				continue;
			}

			unset($deletedFiles[$analysedFile]);

			$analysedFileData = $invertedDependencies[$analysedFile];
			$cachedFileHash = $analysedFileData['fileHash'];
			$dependentFiles = $analysedFileData['dependentFiles'];
			$invertedDependenciesToReturn[$analysedFile] = $dependentFiles;
			$usedTraitDependentFiles = $analysedFileData['usedTraitDependentFiles'] ?? [];
			if (count($usedTraitDependentFiles) > 0) {
				$invertedUsedTraitDependenciesToReturn[$analysedFile] = $usedTraitDependentFiles;
			}
			$currentFileHash = $currentFileHashes[$analysedFile];

			if ($cachedFileHash === $currentFileHash) {
				continue;
			}

			$filesToAnalyse[] = $analysedFile;
			if (!array_key_exists($analysedFile, $filteredExportedNodes)) {
				continue;
			}

			$cachedFileExportedNodes = $filteredExportedNodes[$analysedFile];
			$exportedNodesChanged = $this->exportedNodesChanged($analysedFile, $cachedFileExportedNodes);
			if ($exportedNodesChanged === null) {
				if (count($cachedFileExportedNodes) === 0) {
					continue;
				}
				$hasTraitNode = false;
				foreach ($cachedFileExportedNodes as $exportedNode) {
					if ($exportedNode instanceof ExportedTraitNode) {
						$hasTraitNode = true;
						break;
					}
				}

				if (!$hasTraitNode) {
					continue;
				}

				// if the file changed but no exported nodes changed and the file contains a trait
				// reanalyse files with classes using that trait
				// but not other dependent files (a body-only change of a non-trait symbol
				// in the same file does not affect its dependents)

				foreach ($usedTraitDependentFiles as $usedTraitDependentFile) {
					if (!is_file($usedTraitDependentFile)) {
						continue;
					}
					$filesToAnalyse[] = $usedTraitDependentFile;
				}
				continue;
			}

			if ($exportedNodesChanged) {
				$newFileAppeared = true;
			}

			foreach ($dependentFiles as $dependentFile) {
				if (!is_file($dependentFile)) {
					continue;
				}
				$filesToAnalyse[] = $dependentFile;
			}
		}

		foreach (array_keys($deletedFiles) as $deletedFile) {
			if (!array_key_exists($deletedFile, $invertedDependencies)) {
				continue;
			}

			$deletedFileData = $invertedDependencies[$deletedFile];
			$dependentFiles = $deletedFileData['dependentFiles'];
			foreach ($dependentFiles as $dependentFile) {
				if (!is_file($dependentFile)) {
					continue;
				}
				$filesToAnalyse[] = $dependentFile;
			}
		}

		if ($newFileAppeared) {
			foreach (array_keys($filteredErrors) as $fileWithError) {
				$filesToAnalyse[] = $fileWithError;
			}
		}

		foreach ($packageSeededFiles as $packageSeededFile) {
			if (!is_file($packageSeededFile)) {
				continue;
			}
			$filesToAnalyse[] = $packageSeededFile;
		}

		$filesToAnalyse = array_unique($filesToAnalyse);
		$filesToAnalyseCount = count($filesToAnalyse);

		if ($output->isVeryVerbose()) {
			$elapsed = microtime(true) - $startTime;
			$elapsedString = $elapsed > 5
				? sprintf(' in %.1f seconds', $elapsed)
				: '';

			$output->writeLineFormatted(sprintf(
				'Result cache restored%s. %d %s will be reanalysed.',
				$elapsedString,
				$filesToAnalyseCount,
				$filesToAnalyseCount === 1 ? 'file' : 'files',
			));
		}

		return new ResultCache(
			filesToAnalyse: $filesToAnalyse,
			fullAnalysis: false,
			fullAnalysisReason: null,
			lastFullAnalysisTime: $data['lastFullAnalysisTime'],
			meta: $meta,
			errors: $filteredErrors,
			locallyIgnoredErrors: $filteredLocallyIgnoredErrors,
			linesToIgnore: $filteredLinesToIgnore,
			unmatchedLineIgnores: $filteredUnmatchedLineIgnores,
			collectedData: $filteredCollectedData,
			dependencies: $invertedDependenciesToReturn,
			usedTraitDependencies: $invertedUsedTraitDependenciesToReturn,
			packageDependencies: $packageDependencies,
			exportedNodes: $filteredExportedNodes,
			projectExtensionFiles: $data['projectExtensionFiles'],
			currentFileHashes: $currentFileHashes,
		);
	}

	/**
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 */
	private function isMetaDifferent(array $cachedMeta, array $currentMeta): bool
	{
		$projectConfig = $currentMeta['projectConfig'];
		if ($projectConfig !== null) {
			ksort($currentMeta['projectConfig']);

			$currentMeta['projectConfig'] = $this->getPathTransformer()->relativizeProjectConfig($currentMeta['projectConfig']);
			$currentMeta['projectConfig'] = Neon::encode($currentMeta['projectConfig']);
		}

		return $cachedMeta !== $currentMeta;
	}

	/**
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 *
	 * @return string[]
	 */
	private function getMetaKeyDifferences(array $cachedMeta, array $currentMeta): array
	{
		// Normalize projectConfig the same way isMetaDifferent() does: the cached value is a
		// Neon-encoded string while the current one is a raw array, so a plain === would always
		// report projectConfig as different.
		$projectConfig = $currentMeta['projectConfig'];
		if ($projectConfig !== null) {
			ksort($currentMeta['projectConfig']);

			$currentMeta['projectConfig'] = $this->getPathTransformer()->relativizeProjectConfig($currentMeta['projectConfig']);
			$currentMeta['projectConfig'] = Neon::encode($currentMeta['projectConfig']);
		}

		$diffs = [];
		foreach ($cachedMeta as $key => $value) {
			if (!array_key_exists($key, $currentMeta)) {
				$diffs[] = $key;
				continue;
			}

			if ($value === $currentMeta[$key]) {
				continue;
			}

			$diffs[] = $key;
		}

		if ($diffs === []) {
			// when none of the keys is different,
			// the order of the keys is the problem
			$diffs[] = 'keyOrder';
		}

		return $diffs;
	}

	/**
	 * @param array<int, RootExportedNode> $cachedFileExportedNodes
	 * @return bool|null null means nothing changed, true means new root symbol appeared, false means nested node changed
	 */
	private function exportedNodesChanged(string $analysedFile, array $cachedFileExportedNodes): ?bool
	{
		if (array_key_exists($analysedFile, $this->fileReplacements)) {
			$analysedFile = $this->fileReplacements[$analysedFile];
		}
		$fileExportedNodes = $this->exportedNodeFetcher->fetchNodes($analysedFile);

		$cachedSymbols = [];
		foreach ($cachedFileExportedNodes as $cachedFileExportedNode) {
			$cachedSymbols[$cachedFileExportedNode->getType()][] = $cachedFileExportedNode->getName();
		}

		$fileSymbols = [];
		foreach ($fileExportedNodes as $fileExportedNode) {
			$fileSymbols[$fileExportedNode->getType()][] = $fileExportedNode->getName();
		}

		if ($cachedSymbols !== $fileSymbols) {
			return true;
		}

		if (count($fileExportedNodes) !== count($cachedFileExportedNodes)) {
			return true;
		}

		foreach ($fileExportedNodes as $i => $fileExportedNodeAgain) {
			$cachedExportedNode = $cachedFileExportedNodes[$i];
			if (!$cachedExportedNode->equals($fileExportedNodeAgain)) {
				return false;
			}
		}

		return null;
	}

	public function process(AnalyserResult $analyserResult, ResultCache $resultCache, Output $output, bool $onlyFiles, bool $save): ResultCacheProcessResult
	{
		$internalErrors = $analyserResult->getInternalErrors();
		$freshErrorsByFile = [];
		foreach ($analyserResult->getErrors() as $error) {
			$freshErrorsByFile[$error->getFilePath()][] = $error;
		}

		$freshLocallyIgnoredErrorsByFile = [];
		foreach ($analyserResult->getLocallyIgnoredErrors() as $error) {
			$freshLocallyIgnoredErrorsByFile[$error->getFilePath()][] = $error;
		}

		$freshCollectedDataByFile = $analyserResult->getCollectedData();

		$meta = $resultCache->getMeta();
		$projectConfigArray = $meta['projectConfig'];
		if ($projectConfigArray !== null) {
			$projectConfigArray = $this->getPathTransformer()->relativizeProjectConfig($projectConfigArray);
			$meta['projectConfig'] = Neon::encode($projectConfigArray);
		}
		$doSave = function (array $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedDataByFile, ?array $dependencies, ?array $usedTraitDependencies, ?array $packageDependencies, array $exportedNodes, array $projectExtensionFiles) use ($internalErrors, $resultCache, $output, $onlyFiles, $meta): bool {
			if ($onlyFiles) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because only files were passed as analysed paths.');
				}
				return false;
			}
			if ($dependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in dependencies.');
				}
				return false;
			}
			if ($usedTraitDependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in used trait dependencies.');
				}
				return false;
			}
			if ($packageDependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in package dependencies.');
				}
				return false;
			}

			if (count($internalErrors) > 0) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of internal errors.');
				}
				return false;
			}

			if (count($this->fileReplacements) > 0) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of --tmp-file and --instead-of CLI options passed (editor mode).');
				}
				return false;
			}

			foreach ($errorsByFile as $errors) {
				foreach ($errors as $error) {
					if (!$error->hasNonIgnorableException()) {
						continue;
					}

					if ($output->isVeryVerbose()) {
						$output->writeLineFormatted(sprintf('Result cache was not saved because of non-ignorable exception: %s', $error->getMessage()));
					}

					return false;
				}
			}

			$this->save($resultCache->getLastFullAnalysisTime(), $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedDataByFile, $dependencies, $usedTraitDependencies, $packageDependencies, $exportedNodes, $projectExtensionFiles, $resultCache->getCurrentFileHashes(), $meta);

			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache is saved.');
			}

			return true;
		};

		if ($resultCache->isFullAnalysis()) {
			$saved = false;
			if ($save !== false) {
				$projectExtensionFiles = [];
				if ($analyserResult->getDependencies() !== null) {
					$projectExtensionFiles = $this->getProjectExtensionFiles($projectConfigArray, $analyserResult->getDependencies());
				}
				$saved = $doSave($freshErrorsByFile, $freshLocallyIgnoredErrorsByFile, $analyserResult->getLinesToIgnore(), $analyserResult->getUnmatchedLineIgnores(), $freshCollectedDataByFile, $analyserResult->getDependencies(), $analyserResult->getUsedTraitDependencies(), $analyserResult->getPackageDependencies(), $analyserResult->getExportedNodes(), $projectExtensionFiles);
			} else {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because it was not requested.');
				}
			}

			return new ResultCacheProcessResult($analyserResult, $saved);
		}

		$errorsByFile = $this->mergeErrors($resultCache, $freshErrorsByFile);
		$locallyIgnoredErrorsByFile = $this->mergeLocallyIgnoredErrors($resultCache, $freshLocallyIgnoredErrorsByFile);
		$collectedDataByFile = $this->mergeCollectedData($resultCache, $freshCollectedDataByFile);
		$dependencies = $this->mergeDependencies($resultCache->getDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getDependencies());
		$usedTraitDependencies = $this->mergeDependencies($resultCache->getUsedTraitDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getUsedTraitDependencies());
		$packageDependencies = $this->mergePackageDependencies($resultCache->getPackageDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getPackageDependencies());
		$exportedNodes = $this->mergeExportedNodes($resultCache, $analyserResult->getExportedNodes());
		$linesToIgnore = $this->mergeLinesToIgnore($resultCache, $analyserResult->getLinesToIgnore());
		$unmatchedLineIgnores = $this->mergeUnmatchedLineIgnores($resultCache, $analyserResult->getUnmatchedLineIgnores());

		$saved = false;
		if ($save !== false) {
			$projectExtensionFiles = [];
			foreach ($resultCache->getProjectExtensionFiles() as $file => [$hash, $isAnalysed, $className]) {
				if ($isAnalysed) {
					continue;
				}

				// keep the same file hashes from the old run
				// so that the message "When you edit them and re-run PHPStan, the result cache will get stale."
				// keeps being shown on subsequent runs
				$projectExtensionFiles[$file] = [$hash, false, $className];
			}
			if ($dependencies !== null) {
				foreach ($this->getProjectExtensionFiles($projectConfigArray, $dependencies) as $file => [$hash, $isAnalysed, $className]) {
					if (!$isAnalysed) {
						continue;
					}

					$projectExtensionFiles[$file] = [$hash, true, $className];
				}
			}
			$saved = $doSave($errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedDataByFile, $dependencies, $usedTraitDependencies, $packageDependencies, $exportedNodes, $projectExtensionFiles);
		}

		$flatErrors = [];
		foreach ($errorsByFile as $fileErrors) {
			foreach ($fileErrors as $fileError) {
				$flatErrors[] = $fileError;
			}
		}

		$flatLocallyIgnoredErrors = [];
		foreach ($locallyIgnoredErrorsByFile as $fileErrors) {
			foreach ($fileErrors as $fileError) {
				$flatLocallyIgnoredErrors[] = $fileError;
			}
		}

		return new ResultCacheProcessResult(new AnalyserResult(
			unorderedErrors: $flatErrors,
			filteredPhpErrors: $analyserResult->getFilteredPhpErrors(),
			allPhpErrors: $analyserResult->getAllPhpErrors(),
			locallyIgnoredErrors: $flatLocallyIgnoredErrors,
			linesToIgnore: $linesToIgnore,
			unmatchedLineIgnores: $unmatchedLineIgnores,
			internalErrors: $internalErrors,
			collectedData: $collectedDataByFile,
			dependencies: $dependencies,
			usedTraitDependencies: $usedTraitDependencies,
			packageDependencies: $packageDependencies,
			exportedNodes: $exportedNodes,
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
		), $saved);
	}

	/**
	 * @param array<string, list<Error>> $freshErrorsByFile
	 * @return array<string, list<Error>>
	 */
	private function mergeErrors(ResultCache $resultCache, array $freshErrorsByFile): array
	{
		$errorsByFile = $resultCache->getErrors();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($errorsByFile[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshErrorsByFile)) {
				unset($errorsByFile[$file]);
				continue;
			}
			$errorsByFile[$file] = $freshErrorsByFile[$file];
		}

		return $errorsByFile;
	}

	/**
	 * @param array<string, list<Error>> $freshLocallyIgnoredErrorsByFile
	 * @return array<string, list<Error>>
	 */
	private function mergeLocallyIgnoredErrors(ResultCache $resultCache, array $freshLocallyIgnoredErrorsByFile): array
	{
		$errorsByFile = $resultCache->getLocallyIgnoredErrors();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($errorsByFile[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshLocallyIgnoredErrorsByFile)) {
				unset($errorsByFile[$file]);
				continue;
			}
			$errorsByFile[$file] = $freshLocallyIgnoredErrorsByFile[$file];
		}

		return $errorsByFile;
	}

	/**
	 * @param CollectorData $freshCollectedDataByFile
	 * @return CollectorData
	 */
	private function mergeCollectedData(ResultCache $resultCache, array $freshCollectedDataByFile): array
	{
		$collectedDataByFile = $resultCache->getCollectedData();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($collectedDataByFile[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshCollectedDataByFile)) {
				unset($collectedDataByFile[$file]);
				continue;
			}
			$collectedDataByFile[$file] = $freshCollectedDataByFile[$file];
		}

		return $collectedDataByFile;
	}

	/**
	 * @param array<string, array<string>> $resultCacheDependencies
	 * @param string[] $filesToAnalyse
	 * @param array<string, array<string>>|null $freshDependencies
	 * @return array<string, array<string>>|null
	 */
	private function mergeDependencies(array $resultCacheDependencies, array $filesToAnalyse, ?array $freshDependencies): ?array
	{
		if ($freshDependencies === null) {
			return null;
		}

		$cachedDependencies = [];
		$filesNoOneIsDependingOn = array_fill_keys(array_keys($resultCacheDependencies), true);
		foreach ($resultCacheDependencies as $file => $filesDependingOnFile) {
			foreach ($filesDependingOnFile as $fileDependingOnFile) {
				$cachedDependencies[$fileDependingOnFile][] = $file;
				unset($filesNoOneIsDependingOn[$fileDependingOnFile]);
			}
		}

		foreach (array_keys($filesNoOneIsDependingOn) as $file) {
			if (array_key_exists($file, $cachedDependencies)) {
				throw new ShouldNotHappenException();
			}

			$cachedDependencies[$file] = [];
		}

		$newDependencies = $cachedDependencies;
		foreach ($filesToAnalyse as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($newDependencies[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshDependencies)) {
				unset($newDependencies[$file]);
				continue;
			}

			$newDependencies[$file] = $freshDependencies[$file];
		}

		return $newDependencies;
	}

	/**
	 * @param array<string, array<RootExportedNode>> $freshExportedNodes
	 * @return array<string, array<RootExportedNode>>
	 */
	private function mergeExportedNodes(ResultCache $resultCache, array $freshExportedNodes): array
	{
		$newExportedNodes = $resultCache->getExportedNodes();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($newExportedNodes[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshExportedNodes)) {
				unset($newExportedNodes[$file]);
				continue;
			}

			$newExportedNodes[$file] = $freshExportedNodes[$file];
		}

		return $newExportedNodes;
	}

	/**
	 * @param array<string, array<string>> $resultCachePackageDependencies
	 * @param string[] $filesToAnalyse
	 * @param array<string, array<string>>|null $freshPackageDependencies
	 * @return array<string, array<string>>|null
	 */
	private function mergePackageDependencies(array $resultCachePackageDependencies, array $filesToAnalyse, ?array $freshPackageDependencies): ?array
	{
		if ($freshPackageDependencies === null) {
			return null;
		}

		$newPackageDependencies = $resultCachePackageDependencies;
		foreach ($filesToAnalyse as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($newPackageDependencies[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshPackageDependencies)) {
				unset($newPackageDependencies[$file]);
				continue;
			}

			$newPackageDependencies[$file] = $freshPackageDependencies[$file];
		}

		return $newPackageDependencies;
	}

	/**
	 * Project package names whose installed version/reference changed between two metadata snapshots,
	 * or null if the change set cannot be reliably determined (then the caller falls back to a full
	 * re-analysis rather than risk under-invalidation).
	 *
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 * @return list<string>|null
	 */
	/**
	 * @param array<string, LinesToIgnore> $freshLinesToIgnore
	 * @return array<string, LinesToIgnore>
	 */
	private function mergeLinesToIgnore(ResultCache $resultCache, array $freshLinesToIgnore): array
	{
		$newLinesToIgnore = $resultCache->getLinesToIgnore();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($newLinesToIgnore[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshLinesToIgnore)) {
				unset($newLinesToIgnore[$file]);
				continue;
			}

			$newLinesToIgnore[$file] = $freshLinesToIgnore[$file];
		}

		return $newLinesToIgnore;
	}

	/**
	 * @param array<string, LinesToIgnore> $freshUnmatchedLineIgnores
	 * @return array<string, LinesToIgnore>
	 */
	private function mergeUnmatchedLineIgnores(ResultCache $resultCache, array $freshUnmatchedLineIgnores): array
	{
		$newUnmatchedLineIgnores = $resultCache->getUnmatchedLineIgnores();
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($newUnmatchedLineIgnores[$file]);
				$file = $this->fileReplacements[$file];
			}
			if (!array_key_exists($file, $freshUnmatchedLineIgnores)) {
				unset($newUnmatchedLineIgnores[$file]);
				continue;
			}

			$newUnmatchedLineIgnores[$file] = $freshUnmatchedLineIgnores[$file];
		}

		return $newUnmatchedLineIgnores;
	}

	/**
	 * @param array<string, list<Error>> $errors
	 * @param array<string, list<Error>> $locallyIgnoredErrors
	 * @param array<string, LinesToIgnore> $linesToIgnore
	 * @param array<string, LinesToIgnore> $unmatchedLineIgnores
	 * @param CollectorData $collectedData
	 * @param array<string, array<string>> $dependencies
	 * @param array<string, array<string>> $usedTraitDependencies
	 * @param array<string, array<string>> $packageDependencies
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 * @param array<string, array{string, bool, string}> $projectExtensionFiles
	 * @param array<string, string> $currentFileHashes
	 * @param mixed[] $meta
	 */
	private function save(
		int $lastFullAnalysisTime,
		array $errors,
		array $locallyIgnoredErrors,
		array $linesToIgnore,
		array $unmatchedLineIgnores,
		array $collectedData,
		array $dependencies,
		array $usedTraitDependencies,
		array $packageDependencies,
		array $exportedNodes,
		array $projectExtensionFiles,
		array $currentFileHashes,
		array $meta,
	): void
	{
		$invertedDependencies = [];
		$filesNoOneIsDependingOn = array_fill_keys(array_keys($dependencies), true);
		foreach ($dependencies as $file => $fileDependencies) {
			foreach ($fileDependencies as $fileDep) {
				if (!array_key_exists($fileDep, $invertedDependencies)) {
					$invertedDependencies[$fileDep] = [
						'fileHash' => $currentFileHashes[$fileDep] ?? $this->getFileHash($fileDep),
						'dependentFiles' => [],
					];
					unset($filesNoOneIsDependingOn[$fileDep]);
				}
				$invertedDependencies[$fileDep]['dependentFiles'][] = $file;
			}
		}

		foreach ($usedTraitDependencies as $file => $fileUsedTraitDependencies) {
			foreach ($fileUsedTraitDependencies as $usedTraitFileDep) {
				if (!array_key_exists($usedTraitFileDep, $invertedDependencies)) {
					$invertedDependencies[$usedTraitFileDep] = [
						'fileHash' => $currentFileHashes[$usedTraitFileDep] ?? $this->getFileHash($usedTraitFileDep),
						'dependentFiles' => [],
						'usedTraitDependentFiles' => [],
					];
					unset($filesNoOneIsDependingOn[$usedTraitFileDep]);
				}
				$invertedDependencies[$usedTraitFileDep]['usedTraitDependentFiles'][] = $file;
			}
		}

		foreach (array_keys($filesNoOneIsDependingOn) as $file) {
			if (array_key_exists($file, $invertedDependencies)) {
				throw new ShouldNotHappenException();
			}

			if (!is_file($file)) {
				continue;
			}

			$invertedDependencies[$file] = [
				'fileHash' => $currentFileHashes[$file] ?? $this->getFileHash($file),
				'dependentFiles' => [],
			];
		}

		ksort($errors);
		ksort($locallyIgnoredErrors);
		ksort($linesToIgnore);
		ksort($unmatchedLineIgnores);
		ksort($collectedData);
		ksort($invertedDependencies);

		foreach ($collectedData as & $collectedDataPerFile) {
			ksort($collectedDataPerFile);
		}

		foreach ($invertedDependencies as $file => $fileData) {
			$dependentFiles = $fileData['dependentFiles'];
			sort($dependentFiles);
			$invertedDependencies[$file]['dependentFiles'] = $dependentFiles;

			$usedTraitDependentFiles = $fileData['usedTraitDependentFiles'] ?? [];
			if (count($usedTraitDependentFiles) === 0) {
				continue;
			}

			sort($usedTraitDependentFiles);
			$invertedDependencies[$file]['usedTraitDependentFiles'] = $usedTraitDependentFiles;
		}

		ksort($exportedNodes);

		// The only point where the StubFilesExtensions may run: the analysis is over, bootstrapFiles
		// have been executed, so the extensions can rely on them. restore() reads these hashes back
		// instead of running the extensions again.
		$meta['stubFiles'] = $this->getStubFiles();

		// Store paths relative to the anchor so the cache survives a change of the project's absolute
		// path prefix (a fresh CI checkout dir, a git worktree). projectConfig inside $meta is already a
		// Neon-encoded string here (encoded in process()), so it is relativized at the array level before
		// that encode; only the other meta paths remain.
		$transformer = $this->getPathTransformer();
		$meta = $transformer->relativizeMeta($meta);
		$errors = $transformer->relativizeErrors($errors);
		$locallyIgnoredErrors = $transformer->relativizeErrors($locallyIgnoredErrors);
		$linesToIgnore = $transformer->relativizeCompoundKeyed($linesToIgnore);
		$unmatchedLineIgnores = $transformer->relativizeCompoundKeyed($unmatchedLineIgnores);
		$collectedData = $transformer->relativizeCollectedData($collectedData);
		$invertedDependencies = $transformer->relativizeDependencies($invertedDependencies);
		$packageDependencies = $transformer->relativizeFileKeyed($packageDependencies);
		$exportedNodes = $transformer->relativizeFileKeyed($exportedNodes);
		$projectExtensionFiles = $transformer->relativizeFileKeyed($projectExtensionFiles);

		$file = $this->cacheFilePath;

		// streamed to the file section by section - building the whole
		// var_export()ed contents in memory at once would take up roughly
		// twice the size of the resulting file in the main process
		$handle = @fopen($file, 'w');
		if ($handle === false) {
			$error = error_get_last();
			throw new CouldNotWriteFileException($file, $error !== null ? $error['message'] : 'unknown cause');
		}

		try {
			$this->writeToHandle($handle, $file, "<?php declare(strict_types = 1);

return [
	'lastFullAnalysisTime' => " . var_export($lastFullAnalysisTime, true) . ",
	'meta' => " . var_export($meta, true) . ",
	'projectExtensionFiles' => " . var_export($projectExtensionFiles, true) . ",
	'errorsCallback' => static function (): array { return ");
			$this->streamArrayVarExportToHandle($handle, $file, $errors);
			$this->writeToHandle($handle, $file, "; },
	'locallyIgnoredErrorsCallback' => static function (): array { return ");
			$this->streamArrayVarExportToHandle($handle, $file, $locallyIgnoredErrors);
			$this->writeToHandle($handle, $file, "; },
	'linesToIgnore' => ");
			$this->streamArrayVarExportToHandle($handle, $file, $linesToIgnore);
			$this->writeToHandle($handle, $file, ",
	'unmatchedLineIgnores' => ");
			$this->streamArrayVarExportToHandle($handle, $file, $unmatchedLineIgnores);
			$this->writeToHandle($handle, $file, ",
	'collectedDataCallback' => static function (): array { return ");
			$this->streamArrayVarExportToHandle($handle, $file, $collectedData);
			$this->writeToHandle($handle, $file, "; },
	'dependencies' => ");
			$this->streamArrayVarExportToHandle($handle, $file, $invertedDependencies);
			$this->writeToHandle($handle, $file, ",
	'packageDependencies' => ");
			$this->streamArrayVarExportToHandle($handle, $file, $packageDependencies);
			$this->writeToHandle($handle, $file, ",
	'exportedNodesCallback' => static function (): array { return ");
			$this->streamArrayVarExportToHandle($handle, $file, $exportedNodes);
			$this->writeToHandle($handle, $file, '; },
];
');
		} finally {
			fclose($handle);
		}
	}

	/**
	 * @param resource $handle
	 */
	private function writeToHandle($handle, string $file, string $contents): void
	{
		if (@fwrite($handle, $contents) === false) {
			$error = error_get_last();
			throw new CouldNotWriteFileException($file, $error !== null ? $error['message'] : 'unknown cause');
		}
	}

	/**
	 * Streams the var_export() representation of an array to the file entry
	 * by entry, producing output byte-identical to var_export($values, true).
	 *
	 * var_export() builds the whole export in memory even when told to print it,
	 * so exporting a big section in one call would take up as much memory
	 * as the resulting file section itself.
	 *
	 * Each entry is exported wrapped in a single-entry array whose "array (\n"
	 * prefix and "\n)" suffix are stripped, yielding the same bytes (including
	 * indentation) the entry would get inside the full export. Indenting the lines
	 * of a standalone value export would corrupt multi-line string contents instead.
	 *
	 * @param resource $handle
	 * @param array<mixed> $values
	 */
	private function streamArrayVarExportToHandle($handle, string $file, array $values): void
	{
		$this->writeToHandle($handle, $file, 'array (');
		foreach ($values as $key => $value) {
			$entry = var_export([$key => $value], true);
			$this->writeToHandle($handle, $file, "\n" . substr($entry, 8, -2));
		}

		$this->writeToHandle($handle, $file, "\n)");
	}

	/**
	 * Whether any of the changed Composer packages registers a class in the PHPStan container (a rule,
	 * extension, and so on). Such code can affect the analysis of every file, so the file-granular
	 * package re-seed is not enough and the whole cache must be invalidated.
	 *
	 * @param mixed[]|null $projectConfig
	 * @param array<string, true> $changedPackagesLookup
	 */
	private function changedPackagesProvideContainerClass(?array $projectConfig, array $changedPackagesLookup): bool
	{
		// Extensions registered directly in the project config (services:/rules:) or via an included
		// extension neon file: resolve each service class to the package that owns its file.
		if ($projectConfig !== null) {
			foreach (ProjectConfigHelper::getServiceClassNames($projectConfig) as $class) {
				try {
					// does not use static reflection to reduce file-parsing, like getProjectExtensionFiles()
					$fileName = (new ReflectionClass($class))->getFileName(); /** @phpstan-ignore argument.type */
				} catch (ReflectionException) {
					continue;
				}

				if ($fileName === false || str_starts_with($fileName, 'phar://')) {
					continue;
				}

				$package = $this->packageDependencyResolver->resolvePackage($fileName);
				if ($package !== null && array_key_exists($package, $changedPackagesLookup)) {
					return true;
				}
			}
		}

		// Extensions registered through phpstan/extension-installer are not part of the project config;
		// its generated list is keyed by the extension's Composer package name.
		if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
			foreach (array_keys(GeneratedConfig::EXTENSIONS) as $package) {
				if (array_key_exists($package, $changedPackagesLookup)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param mixed[]|null $projectConfig
	 * @param array<string, mixed> $dependencies
	 * @return array<string, array{string, bool, string}>
	 */
	private function getProjectExtensionFiles(?array $projectConfig, array $dependencies): array
	{
		$this->alreadyProcessed = [];
		$projectExtensionFiles = [];
		if ($projectConfig !== null) {
			$vendorDirs = [];
			foreach ($this->composerAutoloaderProjectPaths as $autoloaderProjectPath) {
				$composer = ComposerHelper::getComposerConfig($autoloaderProjectPath);
				if ($composer === null) {
					continue;
				}
				$vendorDirectory = ComposerHelper::getVendorDirFromComposerConfig($autoloaderProjectPath, $composer);
				$vendorDirs[] = $this->fileHelper->normalizePath($vendorDirectory);
			}

			$classes = ProjectConfigHelper::getServiceClassNames($projectConfig);
			foreach ($classes as $class) {
				try {
					// does not use static reflection to reduce file-parsing
					$classReflection = new ReflectionClass($class); /** @phpstan-ignore argument.type */
				} catch (ReflectionException) {
					continue;
				}

				$fileName = $classReflection->getFileName();
				if ($fileName === false) {
					continue;
				}

				if (str_starts_with($fileName, 'phar://')) {
					continue;
				}

				$allServiceFiles = $this->getAllDependencies($fileName, $dependencies);
				if (count($allServiceFiles) === 0) {
					$normalizedFileName = $this->fileHelper->normalizePath($fileName);
					foreach ($vendorDirs as $vendorDir) {
						if (str_starts_with($normalizedFileName, $vendorDir)) {
							continue 2;
						}
					}
					$projectExtensionFiles[$fileName] = [$this->getFileHash($fileName), false, $class];
					continue;
				}

				foreach ($allServiceFiles as $serviceFile) {
					if (array_key_exists($serviceFile, $projectExtensionFiles)) {
						continue;
					}

					$projectExtensionFiles[$serviceFile] = [$this->getFileHash($serviceFile), true, $class];
				}
			}
		}

		return $projectExtensionFiles;
	}

	/**
	 * @param array<string, array<int, string>> $dependencies
	 * @return array<int, string>
	 */
	private function getAllDependencies(string $fileName, array $dependencies): array
	{
		if (!array_key_exists($fileName, $dependencies)) {
			return [];
		}

		if (array_key_exists($fileName, $this->alreadyProcessed)) {
			return [];
		}

		$this->alreadyProcessed[$fileName] = true;

		$files = [$fileName];

		if ($this->checkDependenciesOfProjectExtensionFiles) {
			foreach ($dependencies[$fileName] as $fileDep) {
				foreach ($this->getAllDependencies($fileDep, $dependencies) as $fileDep2) {
					$files[] = $fileDep2;
				}
			}
		}

		return $files;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param mixed[]|null $projectConfigArray
	 * @return mixed[]
	 */
	private function getMeta(array $allAnalysedFiles, ?array $projectConfigArray): array
	{
		$extensions = array_values(array_filter(get_loaded_extensions(), static fn (string $extension): bool => !in_array($extension, self::EXTENSIONS_NOT_INVALIDATING_CACHE, true)));
		sort($extensions);

		if ($projectConfigArray !== null) {
			foreach ($this->parametersNotInvalidatingCache as $parameterPath) {
				$pathAsArray = is_array($parameterPath) ? $parameterPath : explode('.', $parameterPath);
				ArrayHelper::unsetKeyAtPath($projectConfigArray, $pathAsArray);
			}

			ksort($projectConfigArray);
		}

		return [
			'cacheVersion' => self::CACHE_VERSION,
			'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
			'metaExtensions' => $this->getMetaFromPhpStanExtensions(),
			'phpVersion' => PHP_VERSION_ID,
			'projectConfig' => $projectConfigArray,
			'analysedPaths' => $this->analysedPaths,
			'scannedFiles' => $this->getScannedFiles($allAnalysedFiles),
			'composerLocks' => $this->getComposerLocks(),
			'composerInstalled' => $this->getComposerInstalled(),
			'executedFilesHashes' => $this->getExecutedFileHashes(),
			'phpExtensions' => $extensions,
			// only the statically configured stub files - the full list including the ones
			// from StubFilesExtensions is recorded at save time (see save()), because the
			// extensions may only run after the bootstrapFiles. This entry catches a changed
			// list in any config file, including the included ones that are not part of the
			// projectConfig entry above.
			'configStubFiles' => array_map(fn (string $stubFile): string => $this->fileHelper->normalizePath($stubFile), $this->configStubFiles),
			'level' => $this->usedLevel,
		];
	}

	private function getFileHash(string $path): string
	{
		if (array_key_exists($path, $this->fileReplacements)) {
			$path = $this->fileReplacements[$path];
		}
		if (array_key_exists($path, $this->fileHashes)) {
			return $this->fileHashes[$path];
		}

		$hash = hash_file('sha256', $path);
		if ($hash === false) {
			throw new CouldNotReadFileException($path);
		}
		$this->fileHashes[$path] = $hash;

		return $hash;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @return array<string, string>
	 */
	private function getScannedFiles(array $allAnalysedFiles): array
	{
		$scannedFiles = $this->scanFiles;
		$analysedDirectories = [];
		foreach (array_merge($this->analysedPaths, $this->analysedPathsFromConfig) as $analysedPath) {
			if (is_file($analysedPath)) {
				continue;
			}

			if (!is_dir($analysedPath)) {
				continue;
			}

			$analysedDirectories[] = $analysedPath;
		}

		$directories = array_unique(array_merge($analysedDirectories, $this->scanDirectories));
		foreach ($this->scanFileFinder->findFiles($directories)->getFiles() as $file) {
			$scannedFiles[] = $file;
		}

		$hashes = [];
		foreach (array_diff($scannedFiles, $allAnalysedFiles) as $file) {
			$hashes[$file] = $this->getFileHash($file);
		}

		ksort($hashes);

		return $hashes;
	}

	/**
	 * @return array<string, string>
	 */
	private function getExecutedFileHashes(): array
	{
		$hashes = [];
		if ($this->cliAutoloadFile !== null) {
			$hashes[$this->cliAutoloadFile] = $this->getFileHash($this->cliAutoloadFile);
		}

		foreach ($this->bootstrapFiles as $bootstrapFile) {
			$hashes[$bootstrapFile] = $this->getFileHash($bootstrapFile);
		}

		ksort($hashes);

		return $hashes;
	}

	/**
	 * @return array<string, string>
	 */
	private function getComposerLocks(): array
	{
		$locks = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$lockPath = $autoloadPath . '/composer.lock';
			if (!is_file($lockPath)) {
				continue;
			}

			$locks[$lockPath] = $this->getFileHash($lockPath);
		}

		return $locks;
	}

	/**
	 * @return array<string, array<mixed>>
	 */
	private function getComposerInstalled(): array
	{
		$data = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$composer = ComposerHelper::getComposerConfig($autoloadPath);

			if ($composer === null) {
				continue;
			}

			$filePath = ComposerHelper::getVendorDirFromComposerConfig($autoloadPath, $composer) . '/composer/installed.php';
			if (!is_file($filePath)) {
				continue;
			}

			$installed = require $filePath;
			if (!is_array($installed)) {
				throw new ShouldNotHappenException();
			}

			$rootName = $installed['root']['name'];
			unset($installed['root']);
			unset($installed['versions'][$rootName]);

			$data[$filePath] = $installed;
		}

		return $data;
	}

	/**
	 * @return array<string, string>
	 */
	private function getStubFiles(): array
	{
		$stubFiles = [];
		foreach ($this->stubFilesProvider->getProjectStubFiles() as $stubFile) {
			$stubFiles[$stubFile] = $this->getFileHash($stubFile);
		}

		ksort($stubFiles);

		return $stubFiles;
	}

	/**
	 * @return array<string, string>
	 * @throws ShouldNotHappenException
	 */
	private function getMetaFromPhpStanExtensions(): array
	{
		$meta = [];

		/** @var ResultCacheMetaExtension $extension */
		foreach ($this->resultCacheMetaExtensions->getAll() as $extension) {
			if (array_key_exists($extension->getKey(), $meta)) {
				throw new ShouldNotHappenException(sprintf(
					'Duplicate ResultCacheMetaExtension with key "%s" found.',
					$extension->getKey(),
				));
			}

			$meta[$extension->getKey()] = $extension->getHash();
		}

		ksort($meta);

		return $meta;
	}

}
