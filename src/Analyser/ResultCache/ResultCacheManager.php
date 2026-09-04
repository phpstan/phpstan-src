<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Closure;
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
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function error_get_last;
use function explode;
use function fclose;
use function fgets;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_loaded_extensions;
use function getmypid;
use function hash_file;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_string;
use function ksort;
use function microtime;
use function rename;
use function rtrim;
use function serialize;
use function sort;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function stream_copy_to_stream;
use function strlen;
use function substr;
use function time;
use function uasort;
use function uniqid;
use function unlink;
use function unserialize;
use const PHP_VERSION_ID;
use const SEEK_CUR;

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

	private const CACHE_VERSION = 'v19-lazyCollectedData';

	/**
	 * The recorded hash of a dependency that does not exist. A rule can depend on a path rather than on
	 * a symbol - the file named in a require() - and such a dependency has to be watched while it is
	 * missing, so that creating it re-analyses the file that named it. No real hash is empty.
	 */
	private const MISSING_FILE_HASH = '';

	private const SCANNED_FILE_APPEARED = 'appeared';
	private const SCANNED_FILE_EDITED = 'edited';
	private const SCANNED_FILE_GONE = 'gone';

	/**
	 * Metadata keys whose change does not invalidate the whole result cache: the analysed files they
	 * affect can be pinpointed and re-analysed on their own. See restore().
	 *
	 * The scannedFiles entry covers scanFiles, scanDirectories and the files excluded from the
	 * analysis but living in an analysed directory - see getScannedFiles(). The bootstrapFiles are
	 * deliberately not here: they are executed, not just read, so a change in one of them can affect
	 * anything about the analysis. They stay in the fully invalidating executedFilesHashes entry.
	 */
	private const PARTIALLY_INVALIDATING_META_KEYS = ['composerLocks', 'composerInstalled', 'scannedFiles'];

	/**
	 * The cache file is serialize() output, but an older PHPStan reading it would
	 * include it as PHP and echo the whole multi-megabyte content to stdout as
	 * inline text before discarding it. This prefix makes such an include return
	 * null immediately (the text after ?> is never reached), so a downgrade
	 * degrades to a silent full analysis instead.
	 */
	private const SERIALIZED_FILE_PREFIX = '<?php return; ?>';

	/**
	 * Sections restore() hands back as callbacks instead of arrays, so a run that never asks for them
	 * never pays for decoding them. Each is a whole array frame in the file, so the reader only has to
	 * remember where it starts and walk past its entries.
	 *
	 * Nothing closure-shaped is written: a frame holds the plain serialized payload
	 * (`s:8:"file.php";` followed by `a:1:{i:0;O:22:"PHPStan\Analyser\Error"...`), and
	 * readCacheFile() builds the callback in PHP around the open handle and that offset, which is only
	 * the shape restore() expects. The var_export format writes a real `static function (): array`
	 * into the file instead, and PHP still compiles the array literal inside it.
	 */
	private const LAZY_SECTIONS = ['errors', 'locallyIgnoredErrors', 'exportedNodes'];

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
		private PhpVersion $phpVersion,
		private ComposerPhpVersionFactory $composerPhpVersionFactory,
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
			collectedData: LazyCollectedData::fromArray([]),
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
			// The cache used to be a var_export'd PHP file loaded via include. Including a
			// multi-megabyte PHP source retains its compiled op_arrays and interned strings
			// for the process lifetime; unserialize() produces only the values. A cache file
			// in the old PHP format fails to unserialize and is discarded below like any
			// other corrupted file, so no cache version bump is needed for the transition.
			$data = $this->readCacheFile($cacheFilePath);
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
		$data['collectedDataIndex'] = $transformer->absolutizeFileKeyed($data['collectedDataIndex']);

		$errorsCallback = $data['errorsCallback'];
		$data['errorsCallback'] = static fn (): array => $transformer->absolutizeErrors($errorsCallback());
		$locallyIgnoredErrorsCallback = $data['locallyIgnoredErrorsCallback'];
		$data['locallyIgnoredErrorsCallback'] = static fn (): array => $transformer->absolutizeErrors($locallyIgnoredErrorsCallback());
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
		$notAnalysedFileSymbolsChanged = false;
		// Scanned files whose exported nodes differ from the ones the cache holds - the only ones that
		// make anything be re-analysed. A scanned file can change without any of its symbols changing,
		// and then nothing here has to happen at all.
		$scannedFilesWithChangedSymbols = [];
		if ($this->isMetaDifferent($data['meta'], $meta)) {
			$diffs = $this->getMetaKeyDifferences($data['meta'], $meta);

			// Some metadata differences do not invalidate the whole analysis, because the code they
			// affect can be pinpointed: a Composer lock change only affects the files depending on a
			// package whose version changed, and a scanned file change only affects the files depending
			// on that file. Any other difference falls back to a full re-analysis.
			if (array_diff($diffs, self::PARTIALLY_INVALIDATING_META_KEYS) !== []) {
				return $this->fullAnalysis(
					'Result cache not used because the metadata do not match: ' . implode(', ', $diffs),
					$allAnalysedFiles,
					$meta,
					$currentFileHashes,
					$output,
				);
			}

			if (in_array('scannedFiles', $diffs, true)) {
				// Files that are scanned but not analysed are recorded as regular file dependencies
				// (NodeDependencies::getNonAnalysedDependencies()) along with their exported nodes, so the
				// loop over the not-analysed files below treats an edited one the way the loop above
				// treats an analysed file: the files depending on it are re-analysed only when its
				// exported nodes changed, and it is that loop which counts it as changed. What is left
				// for the metadata to notice is a file the dependency graph does not know: one that
				// appeared, or an edited one nothing depends on. Either may define a symbol that was
				// reported as unknown somewhere - if it declares any symbol at all - so the files with
				// errors are re-analysed the same way a new analysed file makes them re-analysed. The
				// previous nodes of a file nothing depends on are not kept, so for it "declares a symbol"
				// stands in for "declares a new symbol".
				$changedScannedFiles = $this->getChangedScannedFiles($data['meta'], $meta);
				foreach ($changedScannedFiles as $changedScannedFile => $change) {
					// The scanned files getNonAnalysedDependencies() leaves out: nothing would
					// re-analyse the files depending on them, so the whole cache has to go.
					$notTrackedReason = null;
					if (str_starts_with($changedScannedFile, 'phar://')) {
						$notTrackedReason = 'is inside a PHAR';
					} elseif ($this->packageDependencyResolver->resolvePackage($changedScannedFile) !== null) {
						$notTrackedReason = 'belongs to a Composer package';
					}

					if ($notTrackedReason !== null) {
						return $this->fullAnalysis(
							sprintf('Result cache not used because scanned file %s changed and %s.', $changedScannedFile, $notTrackedReason),
							$allAnalysedFiles,
							$meta,
							$currentFileHashes,
							$output,
						);
					}

					if ($change === self::SCANNED_FILE_GONE) {
						continue;
					}

					if ($change === self::SCANNED_FILE_EDITED && array_key_exists($changedScannedFile, $data['dependencies'])) {
						continue;
					}

					if ($this->exportedNodeFetcher->fetchNodes($changedScannedFile) === []) {
						continue;
					}

					$scannedFilesWithChangedSymbols[$changedScannedFile] = true;
					$notAnalysedFileSymbolsChanged = true;
				}
			}

			// The generated container and the analysis are unchanged except for code coming from packages
			// whose version actually changed. Re-analyse just the files depending on a changed package
			// instead of everything; the existing incremental loop below then propagates to their
			// dependents on signature change. An undetermined change set (installed.php cannot be parsed)
			// falls back to a full re-analysis.
			$composerMetaChanged = array_intersect($diffs, ['composerLocks', 'composerInstalled']) !== [];
			$changedPackages = $composerMetaChanged
				? $this->packageDependencyResolver->getChangedComposerPackages($data['meta'], $meta)
				: [];

			if ($changedPackages === null) {
				return $this->fullAnalysis(
					'Result cache not used because the metadata do not match: ' . implode(', ', $diffs),
					$allAnalysedFiles,
					$meta,
					$currentFileHashes,
					$output,
				);
			}

			if ($composerMetaChanged && $changedPackages === []) {
				// The Composer lock/installed metadata changed but no installed package's version or
				// reference did (e.g. a composer.lock regenerated with different formatting or dist/time
				// metadata, common in CI where composer.lock is not committed). Nothing analysis-relevant
				// changed, so keep the restored cache and fall through to the normal incremental analysis
				// instead of re-analysing everything.
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Composer metadata changed but no package versions changed; keeping the result cache.');
				}
			} elseif ($changedPackages !== []) {
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
						'Composer packages changed (%s); re-analysing the files depending on them and the files with errors.',
						implode(', ', $changedPackages),
					));
				}

				// The files depending on a changed package are seeded below, but a file whose error is
				// that a class does not exist depends on nothing: there was no file to record an edge to.
				// An installed package is exactly where such a class tends to arrive from - Composer
				// unpacking a new version, or a plugin writing into vendor/ while it does - so the files
				// with errors are re-analysed too, the same way a new analysed file makes them.
				$notAnalysedFileSymbolsChanged = true;

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
		 * An extension file is code that runs during the analysis, so a change in one can change any
		 * result. That holds whether or not the file is part of the analysed paths - a file outside them
		 * used to only earn a "the result cache will get stale" warning, which was true and left the user
		 * to do something about it.
		 *
		 * @var string $fileHash
		 */
		foreach ($data['projectExtensionFiles'] as $extensionFile => [$fileHash]) {
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
		// Every file the cached graph knows about; the loop over the analysed files below takes out the
		// ones that are still analysed, leaving the deleted files and the files that are depended on
		// without being analysed themselves (scanned files, other project files).
		$notAnalysedFiles = array_fill_keys(array_keys($invertedDependencies), true);
		$filesToAnalyse = [];
		$invertedDependenciesToReturn = [];
		$invertedUsedTraitDependenciesToReturn = [];
		$linesToIgnore = $data['linesToIgnore'];
		$unmatchedLineIgnores = $data['unmatchedLineIgnores'];
		$collectedDataIndex = $data['collectedDataIndex'];

		try {
			// The cached objects are reconstructed here, and a cache written by a PHPStan whose classes
			// have since changed can fail at it: a property the payload does not carry stays
			// uninitialized, and reading it throws. The cacheVersion and phpstanVersion in the metadata
			// keep a released version away from another release's objects, but a source checkout keeps
			// one phpstanVersion across every edit of these classes, so this is reachable there. A cache
			// that cannot be reconstructed is discarded like any other unusable one.
			$errors = $data['errorsCallback']();
			$locallyIgnoredErrors = $data['locallyIgnoredErrorsCallback']();
			$exportedNodes = $data['exportedNodesCallback']();
		} catch (Throwable $e) {
			@unlink($cacheFilePath);

			return $this->fullAnalysis(
				sprintf('Result cache not used because the cached results could not be read back: %s', $e->getMessage()),
				$allAnalysedFiles,
				$meta,
				$currentFileHashes,
				$output,
			);
		}
		$filteredErrors = [];
		$filteredLocallyIgnoredErrors = [];
		$filteredLinesToIgnore = [];
		$filteredUnmatchedLineIgnores = [];
		$filteredCollectedDataIndex = [];
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
			if (array_key_exists($analysedFile, $collectedDataIndex)) {
				$filteredCollectedDataIndex[$analysedFile] = $collectedDataIndex[$analysedFile];
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

			unset($notAnalysedFiles[$analysedFile]);

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
			// A file that declared nothing has no entry at all - save() only writes one for a file with
			// at least one exported node - and a missing entry is not the same as nothing to propagate:
			// the file may have gained its first symbol, which is exactly what the files with errors are
			// waiting for. Comparing against an empty list says so, and says nothing changed when the
			// file still declares nothing.
			$cachedFileExportedNodes = $filteredExportedNodes[$analysedFile] ?? [];
			$exportedNodesChanged = $this->exportedNodesChanged($analysedFile, $cachedFileExportedNodes);
			if ($exportedNodesChanged === null) {
				if (count($cachedFileExportedNodes) === 0) {
					continue;
				}
				if (!$this->hasTraitNode($cachedFileExportedNodes)) {
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

		// the freshly computed metadata, so a file that stopped being scanned is not counted
		$scannedFiles = is_array($meta['scannedFiles'] ?? null) ? $meta['scannedFiles'] : [];
		foreach (array_keys($notAnalysedFiles) as $notAnalysedFile) {
			if (!array_key_exists($notAnalysedFile, $invertedDependencies)) {
				continue;
			}

			$notAnalysedFileData = $invertedDependencies[$notAnalysedFile];
			$dependentFiles = $notAnalysedFileData['dependentFiles'];
			$usedTraitDependentFiles = $notAnalysedFileData['usedTraitDependentFiles'] ?? [];

			$wasMissing = $notAnalysedFileData['fileHash'] === self::MISSING_FILE_HASH;
			if (!is_file($notAnalysedFile) && $wasMissing) {
				// A path that was already missing when the cache was written, and still is: nothing
				// changed, but it stays watched so that creating it re-analyses the files naming it.
				$invertedDependenciesToReturn[$notAnalysedFile] = $dependentFiles;
				if (count($usedTraitDependentFiles) > 0) {
					$invertedUsedTraitDependenciesToReturn[$notAnalysedFile] = $usedTraitDependentFiles;
				}

				continue;
			}

			if (is_file($notAnalysedFile) && $wasMissing) {
				// It exists now. Whether it holds any symbol is beside the point - a file that was named
				// and was not there is now there, and that alone changes what the analysis says.
				$invertedDependenciesToReturn[$notAnalysedFile] = $dependentFiles;
				if (count($usedTraitDependentFiles) > 0) {
					$invertedUsedTraitDependenciesToReturn[$notAnalysedFile] = $usedTraitDependentFiles;
				}

				$dependentFiles = array_merge($dependentFiles, $usedTraitDependentFiles);
			} elseif (is_file($notAnalysedFile)) {
				// Not analysed but still on disk: a scanned file, or another project file the analysed
				// code depends on. Its edges and exported nodes are not carried over by the loop above
				// (that one only walks the analysed files), so they are preserved here.
				$invertedDependenciesToReturn[$notAnalysedFile] = $dependentFiles;
				if (count($usedTraitDependentFiles) > 0) {
					$invertedUsedTraitDependenciesToReturn[$notAnalysedFile] = $usedTraitDependentFiles;
				}

				$cachedFileExportedNodes = $exportedNodes[$notAnalysedFile] ?? null;
				if ($this->getFileHash($notAnalysedFile) === $notAnalysedFileData['fileHash']) {
					if ($cachedFileExportedNodes !== null) {
						$filteredExportedNodes[$notAnalysedFile] = $cachedFileExportedNodes;
					}
					continue;
				}

				// Edited: the same rule as for an analysed file. Nothing the files depending on it can
				// see changed unless its exported nodes did, so a body-only edit re-analyses nothing -
				// except the classes using a trait declared here, whose body is analysed in their
				// context. The fresh nodes replace the cached ones: the file is never analysed, so this is
				// the only place they come from once the file is in the graph.
				$fileExportedNodes = $this->exportedNodeFetcher->fetchNodes($notAnalysedFile);
				$filteredExportedNodes[$notAnalysedFile] = $fileExportedNodes;
				$exportedNodesChanged = $cachedFileExportedNodes !== null
					? $this->compareExportedNodes($cachedFileExportedNodes, $fileExportedNodes)
					: true;
				if ($exportedNodesChanged === null) {
					if (!$this->hasTraitNode($fileExportedNodes)) {
						continue;
					}

					foreach ($usedTraitDependentFiles as $usedTraitDependentFile) {
						if (!is_file($usedTraitDependentFile)) {
							continue;
						}
						$filesToAnalyse[] = $usedTraitDependentFile;
					}
					continue;
				}

				if (array_key_exists($notAnalysedFile, $scannedFiles)) {
					$scannedFilesWithChangedSymbols[$notAnalysedFile] = true;
				}

				if ($exportedNodesChanged) {
					// A symbol appeared or disappeared. The dependents are not enough then: a file that
					// reported the symbol as unknown - or referenced this file while it had a syntax error
					// and no symbols at all - recorded no edge to it, so only the files with errors can
					// bring it back, the same way a new analysed file makes them re-analysed.
					$notAnalysedFileSymbolsChanged = true;
				}

				$dependentFiles = array_merge($dependentFiles, $usedTraitDependentFiles);
			}

			foreach ($dependentFiles as $dependentFile) {
				if (!is_file($dependentFile)) {
					continue;
				}
				$filesToAnalyse[] = $dependentFile;
			}
		}

		if ($newFileAppeared || $notAnalysedFileSymbolsChanged) {
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

		if (count($scannedFilesWithChangedSymbols) > 0 && $output->isVeryVerbose()) {
			$output->writeLineFormatted(sprintf(
				'Scanned files with changed symbols (%d); re-analysing the files affected by them.',
				count($scannedFilesWithChangedSymbols),
			));
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
			collectedData: new LazyCollectedData($filteredCollectedDataIndex, $this->createCollectedDataReader(), []),
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

		return $this->compareExportedNodes($cachedFileExportedNodes, $this->exportedNodeFetcher->fetchNodes($analysedFile));
	}

	/**
	 * @param array<int, RootExportedNode> $cachedFileExportedNodes
	 * @param array<int, RootExportedNode> $fileExportedNodes
	 * @return bool|null null means nothing changed, true means new root symbol appeared, false means nested node changed
	 */
	private function compareExportedNodes(array $cachedFileExportedNodes, array $fileExportedNodes): ?bool
	{
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

		$freshCollectedData = $analyserResult->getLazyCollectedData();

		$meta = $resultCache->getMeta();
		$projectConfigArray = $meta['projectConfig'];
		if ($projectConfigArray !== null) {
			$projectConfigArray = $this->getPathTransformer()->relativizeProjectConfig($projectConfigArray);
			$meta['projectConfig'] = Neon::encode($projectConfigArray);
		}
		// Returns the collected data as it can be read back from the saved file, or null when nothing
		// was saved.
		$doSave = function (array $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, LazyCollectedData $collectedData, ?array $dependencies, ?array $usedTraitDependencies, ?array $packageDependencies, array $exportedNodes, array $projectExtensionFiles) use ($internalErrors, $resultCache, $output, $onlyFiles, $meta): ?LazyCollectedData {
			if ($onlyFiles) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because only files were passed as analysed paths.');
				}
				return null;
			}
			if ($dependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in dependencies.');
				}
				return null;
			}
			if ($usedTraitDependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in used trait dependencies.');
				}
				return null;
			}
			if ($packageDependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in package dependencies.');
				}
				return null;
			}

			if (count($internalErrors) > 0) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of internal errors.');
				}
				return null;
			}

			if (count($this->fileReplacements) > 0) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of --tmp-file and --instead-of CLI options passed (editor mode).');
				}
				return null;
			}

			foreach ($errorsByFile as $errors) {
				foreach ($errors as $error) {
					if (!$error->hasNonIgnorableException()) {
						continue;
					}

					if ($output->isVeryVerbose()) {
						$output->writeLineFormatted(sprintf('Result cache was not saved because of non-ignorable exception: %s', $error->getMessage()));
					}

					return null;
				}
			}

			try {
				$savedCollectedData = $this->save($resultCache->getLastFullAnalysisTime(), $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedData, $dependencies, $usedTraitDependencies, $packageDependencies, $exportedNodes, $projectExtensionFiles, $resultCache->getCurrentFileHashes(), $meta);
			} catch (RuntimeException $e) {
				// Only the copy of the cached collected data throws this: the file it was restored from
				// no longer holds the entries where the index says, so another run sharing the tmpDir
				// replaced it. Its cache stays; the rules on CollectedDataNode discard it when they find
				// the same, and the next run analyses everything.
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted(sprintf('Result cache was not saved because the previous cache file changed during the analysis: %s', $e->getMessage()));
				}
				return null;
			}

			if ($output->isVeryVerbose()) {
				$output->writeLineFormatted('Result cache is saved.');
			}

			return $savedCollectedData;
		};

		if ($resultCache->isFullAnalysis()) {
			$saved = false;
			if ($save !== false) {
				$projectExtensionFiles = [];
				if ($analyserResult->getDependencies() !== null) {
					$projectExtensionFiles = $this->getProjectExtensionFiles($projectConfigArray, $analyserResult->getDependencies());
				}
				$saved = $doSave($freshErrorsByFile, $freshLocallyIgnoredErrorsByFile, $analyserResult->getLinesToIgnore(), $analyserResult->getUnmatchedLineIgnores(), $freshCollectedData, $analyserResult->getDependencies(), $analyserResult->getUsedTraitDependencies(), $analyserResult->getPackageDependencies(), $this->addNonAnalysedExportedNodes($analyserResult->getExportedNodes(), $analyserResult->getDependencies(), $analyserResult->getUsedTraitDependencies()), $projectExtensionFiles) !== null;
			} else {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because it was not requested.');
				}
			}

			return new ResultCacheProcessResult($analyserResult, $saved);
		}

		$errorsByFile = $this->mergeErrors($resultCache, $freshErrorsByFile);
		$locallyIgnoredErrorsByFile = $this->mergeLocallyIgnoredErrors($resultCache, $freshLocallyIgnoredErrorsByFile);
		$collectedData = $this->mergeCollectedData($resultCache, $freshCollectedData->getFresh());
		$dependencies = $this->mergeDependencies($resultCache->getDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getDependencies());
		$usedTraitDependencies = $this->mergeDependencies($resultCache->getUsedTraitDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getUsedTraitDependencies());
		$packageDependencies = $this->mergePackageDependencies($resultCache->getPackageDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getPackageDependencies());
		$exportedNodes = $this->addNonAnalysedExportedNodes($this->mergeExportedNodes($resultCache, $analyserResult->getExportedNodes()), $dependencies, $usedTraitDependencies);
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
			$savedCollectedData = $doSave($errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedData, $dependencies, $usedTraitDependencies, $packageDependencies, $exportedNodes, $projectExtensionFiles);
			$saved = $savedCollectedData !== null;
			// The old file is gone after the rename, so the cached entries are read back from the new one.
			$collectedData = $savedCollectedData ?? $collectedData;
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
			collectedData: $collectedData,
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
	 */
	private function mergeCollectedData(ResultCache $resultCache, array $freshCollectedDataByFile): LazyCollectedData
	{
		$cachedIndex = $resultCache->getCollectedData()->getCachedIndex();
		$mergedFresh = [];
		foreach ($resultCache->getFilesToAnalyse() as $file) {
			if (array_key_exists($file, $this->fileReplacements)) {
				unset($cachedIndex[$file]);
				$file = $this->fileReplacements[$file];
			}
			unset($cachedIndex[$file]);
			if (!array_key_exists($file, $freshCollectedDataByFile)) {
				continue;
			}
			$mergedFresh[$file] = $freshCollectedDataByFile[$file];
		}

		return new LazyCollectedData($cachedIndex, $this->createCollectedDataReader(), $mergedFresh);
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
	 * @param array<string, array<string>> $dependencies
	 * @param array<string, array<string>> $usedTraitDependencies
	 * @param array<string, array<string>> $packageDependencies
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 * @param array<string, array{string, bool, string}> $projectExtensionFiles
	 * @param array<string, string> $currentFileHashes
	 * @param mixed[] $meta
	 * @return LazyCollectedData The same collected data, with the cached entries indexed in the saved file
	 */
	private function save(
		int $lastFullAnalysisTime,
		array $errors,
		array $locallyIgnoredErrors,
		array $linesToIgnore,
		array $unmatchedLineIgnores,
		LazyCollectedData $collectedData,
		array $dependencies,
		array $usedTraitDependencies,
		array $packageDependencies,
		array $exportedNodes,
		array $projectExtensionFiles,
		array $currentFileHashes,
		array $meta,
	): LazyCollectedData
	{
		$invertedDependencies = [];
		$filesNoOneIsDependingOn = array_fill_keys(array_keys($dependencies), true);
		foreach ($dependencies as $file => $fileDependencies) {
			foreach ($fileDependencies as $fileDep) {
				if (!array_key_exists($fileDep, $invertedDependencies)) {
					$invertedDependencies[$fileDep] = [
						'fileHash' => $currentFileHashes[$fileDep] ?? $this->getDependencyFileHash($fileDep),
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
						'fileHash' => $currentFileHashes[$usedTraitFileDep] ?? $this->getDependencyFileHash($usedTraitFileDep),
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
		ksort($invertedDependencies);

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
		$invertedDependencies = $transformer->relativizeDependencies($invertedDependencies);
		$packageDependencies = $transformer->relativizeFileKeyed($packageDependencies);
		$exportedNodes = $transformer->relativizeFileKeyed($exportedNodes);
		$projectExtensionFiles = $transformer->relativizeFileKeyed($projectExtensionFiles);

		$file = $this->cacheFilePath;

		// Written to a sibling of the final path and renamed into place, so a run that dies while
		// saving - or is killed by a CI timeout - leaves the previous cache untouched instead of a
		// half-written one at the path the next run reads. rename() within a directory is atomic, so
		// a concurrent run either reads the whole old cache or the whole new one.
		// Named after the process so two runs sharing a tmpDir cannot write the same temporary file,
		// and so a leftover from a run that was killed is reused rather than accumulating.
		$pid = getmypid();
		$temporaryFile = sprintf('%s.%s.tmp', $file, $pid === false ? uniqid() : $pid);

		// Written frame by frame, and the array sections entry by entry, so the peak cost of saving is
		// one entry rather than the whole cache. Serializing the payload in one call would hold the
		// entire cache in memory twice over - on one project that is 53 MB serialized, 39 MB of it
		// exportedNodes alone - which is the same trap the var_export writer this replaces avoided by
		// streaming.
		$handle = @fopen($temporaryFile, 'w');
		if ($handle === false) {
			$error = error_get_last();
			throw new CouldNotWriteFileException($temporaryFile, $error !== null ? $error['message'] : 'unknown cause');
		}

		$closed = false;
		$renamed = false;

		try {
			$this->writeToHandle($handle, $file, self::SERIALIZED_FILE_PREFIX . "\n");
			$this->writeValueFrame($handle, $file, 'lastFullAnalysisTime', $lastFullAnalysisTime);
			$this->writeValueFrame($handle, $file, 'meta', $meta);
			$this->writeValueFrame($handle, $file, 'projectExtensionFiles', $projectExtensionFiles);
			$this->writeArrayFrame($handle, $file, 'errors', $errors);
			$this->writeArrayFrame($handle, $file, 'locallyIgnoredErrors', $locallyIgnoredErrors);
			$this->writeArrayFrame($handle, $file, 'linesToIgnore', $linesToIgnore);
			$this->writeArrayFrame($handle, $file, 'unmatchedLineIgnores', $unmatchedLineIgnores);
			$savedCollectedDataIndex = $this->writeCollectedDataFrame($handle, $file, $collectedData);
			$this->writeArrayFrame($handle, $file, 'dependencies', $invertedDependencies);
			$this->writeArrayFrame($handle, $file, 'packageDependencies', $packageDependencies);
			$this->writeArrayFrame($handle, $file, 'exportedNodes', $exportedNodes);
			fclose($handle);
			$closed = true;

			if (!@rename($temporaryFile, $file)) {
				$error = error_get_last();
				throw new CouldNotWriteFileException($file, $error !== null ? $error['message'] : 'unknown cause');
			}

			$renamed = true;
		} finally {
			if (!$closed) {
				fclose($handle);
			}

			if (!$renamed) {
				@unlink($temporaryFile);
			}
		}

		return new LazyCollectedData($savedCollectedDataIndex, $this->createCollectedDataReader(), $collectedData->getFresh());
	}

	/**
	 * The collected data section: the cached entries are copied byte for byte from the file they
	 * were restored from, the fresh ones are serialized. Written in the order of the file paths, as
	 * every other section is, so the file does not depend on which files were re-analysed.
	 *
	 * @param resource $handle
	 * @return array<string, array{int, int}> Where every copied entry is in the new file
	 */
	private function writeCollectedDataFrame($handle, string $file, LazyCollectedData $collectedData): array
	{
		$transformer = $this->getPathTransformer();
		$cachedIndex = $collectedData->getCachedIndex();
		$fresh = $collectedData->getFresh();
		$files = array_keys($cachedIndex + $fresh);
		sort($files);
		$this->writeToHandle($handle, $file, 'collectedData* ' . count($files) . "\n");

		$sourceHandle = null;
		$savedIndex = [];
		foreach ($files as $analysedFile) {
			if (array_key_exists($analysedFile, $fresh)) {
				$collectedDataPerFile = $fresh[$analysedFile];
				ksort($collectedDataPerFile);
				foreach ($transformer->relativizeCollectedData([$analysedFile => $collectedDataPerFile]) as $relativeFile => $data) {
					$this->writeEntryFrame($handle, $file, $relativeFile, $data);
				}

				continue;
			}

			$sourceHandle ??= $this->openCacheFile();
			$position = ftell($handle);
			if ($position === false) {
				throw new CouldNotWriteFileException($file, 'cannot tell the position in the file');
			}

			// Nothing holds the file open between restore() and here, so another run sharing the tmpDir
			// may have renamed a different cache over it. The key at the offset says whether the bytes
			// about to be copied are still the entry the index was built for.
			[$offset, $length] = $cachedIndex[$analysedFile];
			$this->seekToEntry($sourceHandle, $offset, $analysedFile);
			if (stream_copy_to_stream($sourceHandle, $handle, $length, $offset) !== $length) {
				throw new RuntimeException(sprintf('The result cache file %s changed while it was being read: the entry of %s is not where it was.', $this->cacheFilePath, $analysedFile));
			}

			$savedIndex[$analysedFile] = [$position, $length];
		}

		if ($sourceHandle !== null) {
			fclose($sourceHandle);
		}

		return $savedIndex;
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
	 * A single value, as `name length\n` followed by that many bytes.
	 *
	 * @param resource $handle
	 */
	private function writeValueFrame($handle, string $file, string $name, mixed $value): void
	{
		$blob = serialize($value);
		$this->writeToHandle($handle, $file, $name . ' ' . strlen($blob) . "\n");
		$this->writeToHandle($handle, $file, $blob);
	}

	/**
	 * An array, as `name* count\n` followed by one entry frame per element.
	 *
	 * @param resource $handle
	 * @param array<mixed> $values
	 */
	private function writeArrayFrame($handle, string $file, string $name, array $values): void
	{
		$this->writeToHandle($handle, $file, $name . '* ' . count($values) . "\n");
		foreach ($values as $key => $value) {
			$this->writeEntryFrame($handle, $file, $key, $value);
		}
	}

	/**
	 * One element of an array frame: `keyLength valueLength\n`, the serialized key, the serialized
	 * value. The key has its own payload so a reader can index the entries without decoding the
	 * values, and serializing it keeps string and integer keys distinct.
	 *
	 * @param resource $handle
	 */
	private function writeEntryFrame($handle, string $file, int|string $key, mixed $value): void
	{
		$keyBlob = serialize($key);
		$valueBlob = serialize($value);
		$this->writeToHandle($handle, $file, strlen($keyBlob) . ' ' . strlen($valueBlob) . "\n");
		$this->writeToHandle($handle, $file, $keyBlob);
		$this->writeToHandle($handle, $file, $valueBlob);
	}

	/**
	 * Read a framed cache file back, one frame at a time.
	 *
	 * Returns null for anything that is not this format, which is how a cache written by an older
	 * PHPStan is detected: the caller discards it and analyses everything, exactly as it does for a
	 * corrupted file.
	 *
	 * @return array<string, mixed>|null
	 */
	private function readCacheFile(string $cacheFilePath): ?array
	{
		$handle = @fopen($cacheFilePath, 'r');
		if ($handle === false) {
			return null;
		}

		$stat = fstat($handle);
		$fileSize = $stat === false ? 0 : $stat['size'];
		$closeHandle = true;

		try {
			if (rtrim((string) fgets($handle), "\n") !== self::SERIALIZED_FILE_PREFIX) {
				return null;
			}

			$data = [];
			$lazy = array_fill_keys(self::LAZY_SECTIONS, false);
			$data['collectedDataIndex'] = [];
			while (($header = fgets($handle)) !== false) {
				$header = rtrim($header, "\n");
				if ($header === '') {
					continue;
				}

				$parts = explode(' ', $header, 2);
				if (count($parts) !== 2) {
					throw new RuntimeException(sprintf('Malformed frame header "%s".', $header));
				}

				[$name, $size] = $parts;
				if (!str_ends_with($name, '*')) {
					$data[$name] = $this->readFrame($handle, (int) $size);

					continue;
				}

				$name = substr($name, 0, -1);
				$count = (int) $size;
				// Never decoded as a whole: it is by far the largest section on a project using collectors,
				// and only the rules on CollectedDataNode need it - after the analysis, in the main process.
				// restore() hands the index out as LazyCollectedData, which reads the entries on demand,
				// and save() copies the ones that stay valid from this file byte for byte.
				if ($name === 'collectedData') {
					$data['collectedDataIndex'] = $this->indexEntryFrames($handle, $count, $fileSize, $name);

					continue;
				}

				if (!array_key_exists($name, $lazy)) {
					$data[$name] = $this->readEntryFrames($handle, $count);

					continue;
				}

				// The entries are walked rather than decoded: that validates the framing of the whole
				// file up front, so a damaged cache is still discarded by restore() instead of failing
				// later inside the callback, and leaves the payloads to be unserialized on demand.
				$offset = ftell($handle);
				if ($offset === false) {
					throw new RuntimeException(sprintf('Cannot tell the position of section "%s".', $name));
				}

				$this->skipEntryFrames($handle, $count, $fileSize, $name);
				$lazy[$name] = true;
				$data[$name . 'Callback'] = fn (): array => $this->readEntryFramesAt($handle, $offset, $count, $name);
			}

			foreach ($lazy as $name => $seen) {
				if ($seen) {
					continue;
				}

				$data[$name . 'Callback'] = static fn (): array => [];
			}

			$closeHandle = false;

			return $data;
		} finally {
			if ($closeHandle) {
				fclose($handle);
			}
		}
	}

	/**
	 * Walks past an array frame's entries decoding only their keys, checking as it goes that the
	 * file really holds them. Returns where each entry is: the offset of its header line and the
	 * length up to the end of its value, which is what stream_copy_to_stream() needs to carry the
	 * entry over to the next cache file unchanged.
	 *
	 * @param resource $handle
	 * @return array<string, array{int, int}>
	 */
	private function indexEntryFrames($handle, int $count, int $fileSize, string $name): array
	{
		$offset = ftell($handle);
		if ($offset === false) {
			throw new RuntimeException(sprintf('Cannot tell the position of section "%s".', $name));
		}

		$index = [];
		for ($i = 0; $i < $count; $i++) {
			[$key, $valueLength] = $this->readEntryKey($handle);
			// The sections are keyed by file path. restore() absolutizes the keys outside the guard that
			// turns a damaged file into a full analysis, so anything else has to be refused here.
			if (!is_string($key)) {
				throw new RuntimeException(sprintf('Entry %d of section "%s" is not keyed by a path.', $i, $name));
			}

			// fseek() past the end of a file succeeds, so the position is what catches a section the
			// file does not actually hold.
			if (fseek($handle, $valueLength, SEEK_CUR) !== 0) {
				throw new RuntimeException(sprintf('Cannot skip entry %d of section "%s".', $i, $name));
			}

			$position = ftell($handle);
			if ($position === false || $position > $fileSize) {
				throw new RuntimeException(sprintf('Section "%s" is truncated at entry %d of %d.', $name, $i, $count));
			}

			$index[$key] = [$offset, $position - $offset];
			$offset = $position;
		}

		return $index;
	}

	/**
	 * Walks past an array frame's entries without decoding anything, checking as it goes that the
	 * file really holds them.
	 *
	 * @param resource $handle
	 */
	private function skipEntryFrames($handle, int $count, int $fileSize, string $name): void
	{
		for ($i = 0; $i < $count; $i++) {
			[$keyLength, $valueLength] = $this->readEntryHeader($handle);

			// fseek() past the end of a file succeeds, so the position is what catches a section the
			// file does not actually hold.
			if (fseek($handle, $keyLength + $valueLength, SEEK_CUR) !== 0) {
				throw new RuntimeException(sprintf('Cannot skip entry %d of section "%s".', $i, $name));
			}

			$position = ftell($handle);
			if ($position === false || $position > $fileSize) {
				throw new RuntimeException(sprintf('Section "%s" is truncated at entry %d of %d.', $name, $i, $count));
			}
		}
	}

	/**
	 * @param resource $handle
	 * @return array{int, int} The lengths of the key and value payloads
	 */
	private function readEntryHeader($handle): array
	{
		$header = fgets($handle);
		if ($header === false) {
			throw new RuntimeException('The cache file ended inside an array section.');
		}

		$lengths = explode(' ', rtrim($header, "\n"));
		if (count($lengths) !== 2 || (int) $lengths[0] <= 0 || (int) $lengths[1] <= 0) {
			throw new RuntimeException(sprintf('Malformed entry header "%s".', rtrim($header, "\n")));
		}

		return [(int) $lengths[0], (int) $lengths[1]];
	}

	/**
	 * The header and key of an entry frame, leaving the handle at its value.
	 *
	 * @param resource $handle
	 * @return array{int|string, int} The key and the length of the value payload
	 */
	private function readEntryKey($handle): array
	{
		[$keyLength, $valueLength] = $this->readEntryHeader($handle);
		$key = $this->readFrame($handle, $keyLength);
		if (!is_int($key) && !is_string($key)) {
			throw new RuntimeException('An entry key is not an array key.');
		}

		return [$key, $valueLength];
	}

	/**
	 * @return Closure(array<string, array{int, int}>): CollectorData
	 */
	private function createCollectedDataReader(): Closure
	{
		return fn (array $index): array => $this->readCollectedData($index);
	}

	/**
	 * Reads the collected data entries the index points at. The file is opened for the duration of
	 * the call only: an open handle would be inherited by the forked workers, and a rename over the
	 * file (the next save) must not find it open on Windows.
	 *
	 * @param array<string, array{int, int}> $index
	 * @return CollectorData
	 */
	private function readCollectedData(array $index): array
	{
		// Read front to back: the entries are stored in the order of their paths and the index
		// usually is too, but a seek backwards on a file this size is what makes reading it slow.
		uasort($index, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

		$transformer = $this->getPathTransformer();
		$handle = $this->openCacheFile();
		$data = [];
		try {
			foreach ($index as $file => [$offset]) {
				[$key, $valueLength] = $this->seekToEntry($handle, $offset, $file);
				$value = $this->readFrame($handle, $valueLength);
				if (!is_array($value)) {
					throw new RuntimeException(sprintf('The collected data of %s is not an array.', $file));
				}

				$data[$key] = $value;
			}
		} catch (Throwable $e) {
			// The walk in readCacheFile() validated the framing, not the values, so this is the first
			// point where a damaged value shows, and save() has already carried it over into the new
			// file. Left in place it would fail every run from now on; restore() discards a cache it
			// cannot read back the same way.
			fclose($handle);
			@unlink($this->cacheFilePath);

			throw new RuntimeException(sprintf('The result cache file %s could not be read back and was discarded, so the next run analyses everything: %s', $this->cacheFilePath, $e->getMessage()), previous: $e);
		}

		fclose($handle);

		return $transformer->absolutizeCollectedData($data);
	}

	/**
	 * Moves to an entry of the collected data section and past its key, refusing an entry that is
	 * not the one the index was built for.
	 *
	 * @param resource $handle
	 * @return array{string, int} The key as stored and the length of the value payload
	 */
	private function seekToEntry($handle, int $offset, string $file): array
	{
		if (fseek($handle, $offset) !== 0) {
			throw new RuntimeException(sprintf('Cannot seek to the collected data of %s.', $file));
		}

		[$key, $valueLength] = $this->readEntryKey($handle);
		if (!is_string($key) || $this->getPathTransformer()->absolutizePath($key) !== $file) {
			throw new RuntimeException(sprintf('The result cache file %s changed while it was being read: the entry of %s is not where it was.', $this->cacheFilePath, $file));
		}

		return [$key, $valueLength];
	}

	/**
	 * @return resource
	 */
	private function openCacheFile()
	{
		$handle = @fopen($this->cacheFilePath, 'r');
		if ($handle === false) {
			throw new RuntimeException(sprintf('Cannot open the result cache file %s.', $this->cacheFilePath));
		}

		return $handle;
	}

	/**
	 * @param resource $handle
	 * @return array<mixed>
	 */
	private function readEntryFramesAt($handle, int $offset, int $count, string $name): array
	{
		if (fseek($handle, $offset) !== 0) {
			throw new RuntimeException(sprintf('Cannot seek to section "%s".', $name));
		}

		return $this->readEntryFrames($handle, $count);
	}

	/**
	 * @param resource $handle
	 * @return array<mixed>
	 */
	private function readEntryFrames($handle, int $count): array
	{
		$entries = [];
		for ($i = 0; $i < $count; $i++) {
			[$key, $valueLength] = $this->readEntryKey($handle);
			$entries[$key] = $this->readFrame($handle, $valueLength);
		}

		return $entries;
	}

	/**
	 * A frame's payload, or an exception when the file does not hold one.
	 *
	 * Every failure here means a cache file that is this format but damaged: a CI cache artifact
	 * archived or restored half way, an interrupted copy, a disk that filled up. restore() turns
	 * the exception into a discarded cache and a full analysis, the same way it handles the parse
	 * error an incomplete var_export'd file used to produce. Returning a value instead would
	 * hand a half-read cache to the caller, where the missing pieces surface as type errors far
	 * from the cause.
	 *
	 * false is treated as failure because unserialize() reports failure that way and no value in
	 * the cache is a bare false: the sections are arrays and lastFullAnalysisTime is an int.
	 *
	 * @param resource $handle
	 */
	private function readFrame($handle, int $length): mixed
	{
		if ($length <= 0) {
			throw new RuntimeException(sprintf('Frame length %d is not positive.', $length));
		}

		$blob = fread($handle, $length);
		if ($blob === false || strlen($blob) !== $length) {
			throw new RuntimeException(sprintf(
				'Expected a %d byte frame, read %d bytes.',
				$length,
				$blob === false ? 0 : strlen($blob),
			));
		}

		$value = @unserialize($blob);
		if ($value === false) {
			throw new RuntimeException(sprintf('A %d byte frame could not be unserialized.', $length));
		}

		return $value;
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
					if ($this->isFileOfInstalledPackage($this->fileHelper->normalizePath($fileName), $vendorDirs)) {
						continue;
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
	 * Whether the file belongs to a package Composer installed, which only changes when Composer
	 * changes it - editing one is not a thing to do, and a version change is noticed through the
	 * metadata instead. A package from a path repository is the exception: it is the project's own
	 * code, sitting in vendor/ because Composer copied it there, and it is edited in place.
	 *
	 * @param list<string> $vendorDirs
	 */
	private function isFileOfInstalledPackage(string $normalizedFileName, array $vendorDirs): bool
	{
		foreach ($vendorDirs as $vendorDir) {
			if (!str_starts_with($normalizedFileName, $vendorDir)) {
				continue;
			}

			$package = $this->packageDependencyResolver->resolvePackage($normalizedFileName);

			return $package === null || !$this->packageDependencyResolver->isPathPackage($package);
		}

		return false;
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
	 * Scanned files that differ between two metadata snapshots, each mapped to what happened to it.
	 *
	 * @param mixed[] $cachedMeta
	 * @param mixed[] $currentMeta
	 * @return array<string, self::SCANNED_FILE_*>
	 */
	private function getChangedScannedFiles(array $cachedMeta, array $currentMeta): array
	{
		$cached = is_array($cachedMeta['scannedFiles'] ?? null) ? $cachedMeta['scannedFiles'] : [];
		$current = is_array($currentMeta['scannedFiles'] ?? null) ? $currentMeta['scannedFiles'] : [];

		$changed = [];
		foreach ($current as $file => $hash) {
			if (!array_key_exists($file, $cached)) {
				$changed[$file] = self::SCANNED_FILE_APPEARED;
				continue;
			}

			if ($cached[$file] === $hash) {
				continue;
			}

			$changed[$file] = self::SCANNED_FILE_EDITED;
		}
		foreach (array_keys($cached) as $file) {
			if (array_key_exists($file, $current)) {
				continue;
			}

			$changed[$file] = self::SCANNED_FILE_GONE;
		}

		return $changed;
	}

	/**
	 * @param array<RootExportedNode> $exportedNodes
	 */
	private function hasTraitNode(array $exportedNodes): bool
	{
		foreach ($exportedNodes as $exportedNode) {
			if ($exportedNode instanceof ExportedTraitNode) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Exported nodes of the files the analysed code depends on without them being analysed - scanned
	 * files, other project files reached through the autoloader. Nothing analyses them, so their nodes
	 * are fetched here, and restore() compares them with the file's current nodes the same way it does
	 * for an analysed file. Only the files without an entry are fetched: restore() refreshes the entry
	 * of an edited file and drops the entry of a deleted one, the rest is unchanged since the last run.
	 *
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 * @param array<string, array<string>>|null $dependencies
	 * @param array<string, array<string>>|null $usedTraitDependencies
	 * @return array<string, array<RootExportedNode>>
	 */
	private function addNonAnalysedExportedNodes(array $exportedNodes, ?array $dependencies, ?array $usedTraitDependencies): array
	{
		if ($dependencies === null || $usedTraitDependencies === null) {
			return $exportedNodes;
		}

		// every analysed file is a key; a file that only ever appears as a value is not analysed
		foreach ([$dependencies, $usedTraitDependencies] as $graph) {
			foreach ($graph as $fileDependencies) {
				foreach ($fileDependencies as $dependencyFile) {
					if (
						array_key_exists($dependencyFile, $dependencies)
						|| array_key_exists($dependencyFile, $exportedNodes)
						|| !is_file($dependencyFile)
					) {
						continue;
					}

					$exportedNodes[$dependencyFile] = $this->exportedNodeFetcher->fetchNodes($dependencyFile);
				}
			}
		}

		return $exportedNodes;
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

		// The cached meta comes back through absolutizeMeta(), which normalizes every path, and
		// isMetaDifferent() compares with !==. The paths below are spelled by their sources, not by
		// PHPStan: a config entry through a %placeholder% NeonAdapter could not expand keeps its '..'
		// segments (and mixes separators on Windows), Composer records install_path as
		// __DIR__ . '/../x', --autoload-file may be given as ./vendor/autoload.php. Normalizing them
		// here makes the comparison one of paths, not of spellings - otherwise such an entry reads
		// as a metadata change on every run and the whole cache is discarded every time.
		$composerMinPhpVersion = $this->composerPhpVersionFactory->getMinVersion();
		$composerMaxPhpVersion = $this->composerPhpVersionFactory->getMaxVersion();

		return $this->getPathTransformer()->normalizeMeta([
			'cacheVersion' => self::CACHE_VERSION,
			'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
			'metaExtensions' => $this->getMetaFromPhpStanExtensions(),
			'phpVersion' => PHP_VERSION_ID,
			// The version the analysis targets, which is not the one PHPStan runs on: it can come from
			// the phpVersion parameter, or from config.platform.php in composer.json. The parameter is
			// part of projectConfig, but composer.json is hashed nowhere - and the two version ranges
			// below come from its require section, which is not in composer.lock's content hash either,
			// so a composer update need not move anything else in this metadata.
			'phpVersionForAnalysis' => [$this->phpVersion->getVersionId(), $this->phpVersion->getSource()],
			'composerPhpVersionRange' => [
				$composerMinPhpVersion !== null ? $composerMinPhpVersion->getVersionId() : null,
				$composerMaxPhpVersion !== null ? $composerMaxPhpVersion->getVersionId() : null,
			],
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
			'configStubFiles' => $this->configStubFiles,
			'level' => $this->usedLevel,
		]);
	}

	/**
	 * The hash of a file that is depended on, which is allowed not to exist - see MISSING_FILE_HASH.
	 */
	private function getDependencyFileHash(string $path): string
	{
		if (!is_file($path)) {
			return self::MISSING_FILE_HASH;
		}

		return $this->getFileHash($path);
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
