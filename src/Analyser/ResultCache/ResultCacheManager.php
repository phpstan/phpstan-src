<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Generator;
use Nette\Neon\Neon;
use PhpParser\Node;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\CollectedDataRuleResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Analyser\FinalizerResult;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
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
use PHPStan\File\FileStatSignatures;
use PHPStan\Internal\ArrayHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;
use function array_diff;
use function array_diff_key;
use function array_fill_keys;
use function array_filter;
use function array_intersect;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function clearstatcache;
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
use function md5;
use function microtime;
use function min;
use function rename;
use function rtrim;
use function serialize;
use function sort;
use function sprintf;
use function stat;
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
 * @phpstan-import-type ValueDependencies from ValueDependencyCollector
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

	private const CACHE_VERSION = 'v26-collectedDataPerCollector';

	/**
	 * The recorded hash of a dependency that does not exist. A rule can depend on a path rather than on
	 * a symbol - the file named in a require() - and such a dependency has to be watched while it is
	 * missing, so that creating it re-analyses the file that named it. No real hash is empty.
	 */
	private const MISSING_FILE_HASH = '';

	/**
	 * Prefixes the recorded hash of an analysed file with a parse error, whose result is not cached,
	 * or of one that changed during the analysis, or uses one that did. The prefixed hash matches no
	 * real hash, so the file is re-analysed by the next run.
	 */
	private const REANALYSE_FILE_HASH_PREFIX = 'reanalyse:';

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
	private const PARTIALLY_INVALIDATING_META_KEYS = ['composerLocks', 'composerInstalled', 'extensionVersions', 'scannedFiles'];

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

	/**
	 * The stat signatures of the analysed files whose hash in $fileHashes can be trusted to still
	 * describe them next time the signature matches - see hashAnalysedFiles().
	 *
	 * @var array<string, string>
	 */
	private array $recordedFileStats = [];

	/**
	 * The stat of the analysed files when they were hashed, to tell at save time which of them
	 * changed while they were analysed - see getFilesChangedSinceHashed(). Kept as a string rather
	 * than what stat() returned: it lives through the whole analysis.
	 *
	 * @var array<string, string>
	 */
	private array $hashedFileStats = [];

	/**
	 * The analysed files modified in the second they were hashed in or later.
	 *
	 * @var array<string, true>
	 */
	private array $racilyHashedFiles = [];

	private ?ResultCachePathTransformer $pathTransformer = null;

	/** @var array<string, true> */
	private array $alreadyProcessed = [];

	private bool $restoredCacheUnchanged = false;

	/** @var array<string, string> */
	private array $restoredStubFiles = [];

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $attributeServicesDirectories
	 * @param list<string> $attributeServicesClasses classes registered from attributeServicesDirectories
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
		private PackageDependencyResolver $packageDependencyResolver,
		private ExtensionVersionProvider $extensionVersionProvider,
		#[AutowiredParameter(ref: '%resultCachePath%')]
		private string $cacheFilePath,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private array $attributeServicesDirectories,
		#[AutowiredParameter]
		private array $attributeServicesClasses,
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
		private FileStatSignatures $fileStatSignatures,
		private ValueDependencyCollector $valueDependencyCollector,
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
			valueDependencies: [],
			packageDependencies: [],
			exportedNodes: [],
			cachedExportedNodes: CachedExportedNodes::createEmpty(),
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
		$this->restoredCacheUnchanged = false;
		$this->restoredStubFiles = [];
		$this->recordedFileStats = [];

		$startTime = microtime(true);
		$analysedFileStats = $this->statAnalysedFiles($allAnalysedFiles);
		if ($debug) {
			return $this->fullAnalysis(
				'Result cache not used because of debug mode.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$this->hashAnalysedFiles($analysedFileStats, null),
				$output,
			);
		}
		if ($onlyFiles) {
			return $this->fullAnalysis(
				'Result cache not used because only files were passed as analysed paths.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$this->hashAnalysedFiles($analysedFileStats, null),
				$output,
			);
		}

		$cacheFilePath = $this->cacheFilePath;
		if (!is_file($cacheFilePath)) {
			return $this->fullAnalysis(
				'Result cache not used because the cache file does not exist.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$this->hashAnalysedFiles($analysedFileStats, null),
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
				$this->hashAnalysedFiles($analysedFileStats, null),
				$output,
			);
		}

		if (!is_array($data)) {
			@unlink($cacheFilePath);

			return $this->fullAnalysis(
				'Result cache not used because the cache file is corrupted.',
				$allAnalysedFiles,
				$this->getMeta($allAnalysedFiles, $projectConfigArray),
				$this->hashAnalysedFiles($analysedFileStats, null),
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
		if (array_key_exists('dependencyGraph', $data)) {
			try {
				$data['dependencies'] = $this->decodeDependencyGraph($data['dependencyGraph'], $transformer);
			} catch (Throwable $e) {
				@unlink($cacheFilePath);

				return $this->fullAnalysis(
					sprintf('Result cache not used because an error occurred while loading the cache file: %s', $e->getMessage()),
					$allAnalysedFiles,
					$this->getMeta($allAnalysedFiles, $projectConfigArray),
					$this->hashAnalysedFiles($analysedFileStats, null),
					$output,
				);
			}
			unset($data['dependencyGraph']);
		} else {
			// a cache written by an older version, which the cacheVersion check below discards
			$data['dependencies'] = $transformer->absolutizeDependencies($data['dependencies'] ?? []);
		}
		$currentFileHashes = $this->hashAnalysedFiles($analysedFileStats, $data['dependencies']);
		$fileStatSignaturesChanged = $this->fileStatSignaturesDiffer($data['dependencies']);
		$data['packageDependencies'] = $transformer->absolutizeFileKeyed($data['packageDependencies'] ?? []);
		try {
			$cachedValueDependencies = $this->decodeValueDependencies($data['valueDependencies'] ?? ['paths' => [], 'entries' => []], $transformer);
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
		$data['collectedDataIndex'] = $transformer->absolutizeFileKeyed($data['collectedDataIndex']);

		$errorsCallback = $data['errorsCallback'];
		$data['errorsCallback'] = static fn (): array => $transformer->absolutizeErrors($errorsCallback());
		$locallyIgnoredErrorsCallback = $data['locallyIgnoredErrorsCallback'];
		$data['locallyIgnoredErrorsCallback'] = static fn (): array => $transformer->absolutizeErrors($locallyIgnoredErrorsCallback());
		$exportedNodesCallback = $data['exportedNodesCallback'];
		$data['exportedNodesCallback'] = static fn (): array => $transformer->absolutizeFileKeyed($exportedNodesCallback());

		$cachedExportedNodes = CachedExportedNodes::createEmpty();
		if (array_key_exists('exportedNodesLocations', $data) && is_array($data['exportedNodesLocations'])) {
			$exportedNodesLocations = [];
			foreach ($data['exportedNodesLocations'] as $exportedNodesFile => $exportedNodesLocation) {
				$exportedNodesLocations[$transformer->absolutizePath($exportedNodesFile)] = $exportedNodesLocation;
			}
			$cachedExportedNodes = CachedExportedNodes::createFromFile($data['cacheFileHandle'], $exportedNodesLocations);
		}

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
		$metaDifferent = $this->isMetaDifferent($data['meta'], $meta);
		if ($metaDifferent) {
			$diffs = $this->getMetaKeyDifferences($data['meta'], $meta);

			// Some metadata differences do not invalidate the whole analysis, because the code they
			// affect can be pinpointed: a Composer lock change only affects the files depending on a
			// package whose version changed, a different selected version of a PHP extension only the
			// files using that extension, and a scanned file change only affects the files depending
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
				// (DependencyResolver::resolveFileDependencies()) along with their exported nodes, so the
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
					// The scanned files getFileAndPackageDependencies() leaves out: nothing would
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

			// The same goes for a PHP extension whose stubs differ between its major versions: the files
			// using its symbols recorded a dependency on its platform package (ext-<name>), so switching
			// the version composer.json selects re-analyses just those.
			$changedExtensionPackages = in_array('extensionVersions', $diffs, true)
				? $this->packageDependencyResolver->getChangedExtensionPackages($data['meta'], $meta)
				: [];

			if ($changedPackages === null || $changedExtensionPackages === null) {
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
			}

			if ($changedExtensionPackages !== [] && $output->isVeryVerbose()) {
				$output->writeLineFormatted(sprintf(
					'PHP extension versions changed (%s); re-analysing the files depending on them and the files with errors.',
					implode(', ', $changedExtensionPackages),
				));
			}

			if ($changedPackages !== [] || $changedExtensionPackages !== []) {
				// The files depending on a changed package are seeded below, but a file whose error is
				// that a class does not exist depends on nothing: there was no file to record an edge to.
				// An installed package is exactly where such a class tends to arrive from - Composer
				// unpacking a new version, or a plugin writing into vendor/ while it does - and so is a
				// newly selected extension version, so the files with errors are re-analysed too, the
				// same way a new analysed file makes them.
				$notAnalysedFileSymbolsChanged = true;

				$changedPackagesLookup = array_fill_keys(array_merge($changedPackages, $changedExtensionPackages), true);
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
			$collectedDataRuleResults = $data['collectedDataRuleResults'] ?? [];
			if (!is_array($collectedDataRuleResults)) {
				throw new RuntimeException('The cached results of the rules on collected data are not an array.');
			}
			foreach ($collectedDataRuleResults as $collectedDataRuleResult) {
				if (!$collectedDataRuleResult instanceof CollectedDataRuleResult) {
					throw new RuntimeException('A cached result of a rule on collected data is not a CollectedDataRuleResult.');
				}
			}
			$collectedDataRuleResults = $transformer->absolutizeCollectedDataRuleResults($collectedDataRuleResults);
			$storedFilesWithCollectedDataRuleErrors = $data['filesWithCollectedDataRuleErrors'] ?? [];
			if (!is_array($storedFilesWithCollectedDataRuleErrors)) {
				throw new RuntimeException('The cached files with errors of the rules on collected data are not an array.');
			}
			$filesWithCollectedDataRuleErrors = [];
			foreach ($storedFilesWithCollectedDataRuleErrors as $fileWithCollectedDataRuleError) {
				if (!is_string($fileWithCollectedDataRuleError)) {
					throw new RuntimeException('A cached file with errors of the rules on collected data is not a string.');
				}

				$filesWithCollectedDataRuleErrors[] = $transformer->absolutizePath($fileWithCollectedDataRuleError);
			}
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
		// the analysed and scanned files whose exported nodes stay in the old cache file until save()
		$keptCachedExportedNodes = [];
		$newFileAppeared = false;
		$dependencyFilesChanged = false;
		$cachedExportedNodesError = null;
		$decodeCachedExportedNodes = static function (string $file) use ($cachedExportedNodes, &$cachedExportedNodesError): array {
			try {
				return $cachedExportedNodes->decode($file);
			} catch (CachedExportedNodesUnreadableException $e) {
				$cachedExportedNodesError ??= $e->getMessage();

				return [];
			}
		};

		foreach (array_keys($cachedStubFiles) as $stubFile) {
			if (!array_key_exists($stubFile, $errors)) {
				continue;
			}

			$filteredErrors[$stubFile] = $errors[$stubFile];
		}

		// Whether a changed file's exported nodes changed decides what else has to be re-analysed with
		// it: its dependents, the classes using a trait it declares, and - when a symbol appeared or
		// disappeared - the files with errors. Finding out means parsing the file, serially here in
		// the main process. When all of those files are re-analysed anyway, because they changed
		// themselves, the answer cannot add anything and the file is not parsed. After a branch switch
		// or a formatting run that touched most of the project, that is most of the changed files -
		// on Drupal core with every file edited, 9 seconds of parsing, and workers forked from a main
		// process bloated by the parsed files.
		$changedAnalysedFiles = [];
		foreach ($allAnalysedFiles as $analysedFile) {
			if (
				array_key_exists($analysedFile, $invertedDependencies)
				&& $invertedDependencies[$analysedFile]['fileHash'] === $currentFileHashes[$analysedFile]
			) {
				continue;
			}

			$changedAnalysedFiles[$analysedFile] = true;
		}
		// The errors of the rules on CollectedDataNode are not in $errors: those rules run on the merged
		// collected data on every run, a cached copy would be reported twice. But such an error waits
		// for a symbol to appear just like any other, and re-analysing its file renews the file's share
		// of the collected data.
		$cachedFilesWithCollectedDataRuleErrors = array_fill_keys($filesWithCollectedDataRuleErrors, true);
		$filteredFilesWithCollectedDataRuleErrors = [];

		// only the stub files with errors so far, which are never analysed
		$filesWithErrorsAllChanged = $filteredErrors === [];
		foreach ($allAnalysedFiles as $analysedFile) {
			if (
				(!array_key_exists($analysedFile, $errors) && !array_key_exists($analysedFile, $cachedFilesWithCollectedDataRuleErrors))
				|| array_key_exists($analysedFile, $changedAnalysedFiles)
			) {
				continue;
			}

			$filesWithErrorsAllChanged = false;
			break;
		}

		foreach ($allAnalysedFiles as $analysedFile) {
			if (array_key_exists($analysedFile, $errors)) {
				$filteredErrors[$analysedFile] = $errors[$analysedFile];
			}
			if (array_key_exists($analysedFile, $cachedFilesWithCollectedDataRuleErrors)) {
				$filteredFilesWithCollectedDataRuleErrors[] = $analysedFile;
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
			} elseif ($cachedExportedNodes->has($analysedFile)) {
				$keptCachedExportedNodes[$analysedFile] = true;
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
			if (
				($filesWithErrorsAllChanged || $newFileAppeared)
				&& $this->areAllChanged($dependentFiles, $changedAnalysedFiles)
				&& $this->areAllChanged($usedTraitDependentFiles, $changedAnalysedFiles)
			) {
				continue;
			}

			// A file that declared nothing has no entry at all - save() only writes one for a file with
			// at least one exported node - and a missing entry is not the same as nothing to propagate:
			// the file may have gained its first symbol, which is exactly what the files with errors are
			// waiting for. Comparing against an empty list says so, and says nothing changed when the
			// file still declares nothing.
			$fileExportedNodes = $this->exportedNodeFetcher->fetchNodesIfParsable($this->fileReplacements[$analysedFile] ?? $analysedFile);
			if ($fileExportedNodes === null) {
				// A parse error, typically code saved half typed. The file is re-analysed and reports it,
				// but its symbols count as unchanged: the files using them keep their results until it is
				// fixed, instead of being analysed against a file that declares nothing, and so do the
				// files with errors. process() keeps the symbols it had, which the fixed file is compared
				// with.
				continue;
			}
			$cachedFileExportedNodes = $filteredExportedNodes[$analysedFile]
				?? ($cachedExportedNodes->has($analysedFile) ? $decodeCachedExportedNodes($analysedFile) : []);
			$exportedNodesChanged = $this->compareExportedNodes($cachedFileExportedNodes, $fileExportedNodes);
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

		// in the order process() compares them in
		sort($filteredFilesWithCollectedDataRuleErrors);

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
				$dependencyFilesChanged = true;
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

				if ($this->getFileHash($notAnalysedFile) === $notAnalysedFileData['fileHash']) {
					if (array_key_exists($notAnalysedFile, $exportedNodes)) {
						$filteredExportedNodes[$notAnalysedFile] = $exportedNodes[$notAnalysedFile];
					} elseif ($cachedExportedNodes->has($notAnalysedFile)) {
						$keptCachedExportedNodes[$notAnalysedFile] = true;
					}
					continue;
				}

				$cachedFileExportedNodes = $exportedNodes[$notAnalysedFile]
					?? ($cachedExportedNodes->has($notAnalysedFile) ? $decodeCachedExportedNodes($notAnalysedFile) : null);

				$dependencyFilesChanged = true;
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
			} else {
				$dependencyFilesChanged = true;
			}

			foreach ($dependentFiles as $dependentFile) {
				if (!is_file($dependentFile)) {
					continue;
				}
				$filesToAnalyse[] = $dependentFile;
			}
		}

		if ($cachedExportedNodesError !== null) {
			@unlink($cacheFilePath);

			return $this->fullAnalysis(
				sprintf('Result cache not used because the cached results could not be read back: %s', $cachedExportedNodesError),
				$allAnalysedFiles,
				$meta,
				$currentFileHashes,
				$output,
			);
		}

		// The values declared through DependencyTracker::trackValueDependency() - see ValueDependencyCollector.
		// The files depending on one that is different now are re-analysed, and so are the ones
		// depending on one whose extension is no longer registered, which is then forgotten.
		$allAnalysedFilesSet = array_fill_keys($allAnalysedFiles, true);
		$valueDependenciesToReturn = [];
		$valueDependenciesChanged = false;
		foreach ($cachedValueDependencies as $cachedValueDependency) {
			$extension = $this->valueDependencyCollector->getExtension($cachedValueDependency['extensionClass']);
			$key = $extension !== null ? $extension->keyFromResultCache($cachedValueDependency['storedKey']) : null;
			$currentValue = $extension !== null && $key !== null ? $extension->getValue($key) : null;
			if ($currentValue !== $cachedValueDependency['value']) {
				$valueDependenciesChanged = true;
				foreach ($this->getValueDependentFilesToAnalyse($cachedValueDependency['analysis'], $cachedValueDependency['declarations'], $invertedDependencies, $allAnalysedFilesSet) as $valueDependentFile) {
					$filesToAnalyse[] = $valueDependentFile;
				}
			}
			if ($key === null || $currentValue === null) {
				continue;
			}

			$valueDependenciesToReturn[ValueDependencyCollector::getId($cachedValueDependency['extensionClass'], $key)] = [
				$cachedValueDependency['extensionClass'],
				$key,
				$currentValue,
				$cachedValueDependency['analysis'],
				$cachedValueDependency['declarations'],
			];
		}

		if ($newFileAppeared || $notAnalysedFileSymbolsChanged) {
			foreach (array_keys($filteredErrors) as $fileWithError) {
				// A stub file has errors from the stub validation, which runs on the full analysis only.
				// It is not an analysed file, so analysing it would report the errors of all rules in it.
				if (array_key_exists($fileWithError, $cachedStubFiles)) {
					continue;
				}

				$filesToAnalyse[] = $fileWithError;
			}
			foreach ($filteredFilesWithCollectedDataRuleErrors as $fileWithError) {
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

		$this->restoredCacheUnchanged = !$metaDifferent && !$dependencyFilesChanged && !$fileStatSignaturesChanged && !$valueDependenciesChanged;
		$this->restoredStubFiles = $cachedStubFiles;

		// The merge in process() replaces the entries of re-analysed files without reading them, so
		// they need not stay in memory alongside the fresh results while the analysis runs.
		foreach ($filesToAnalyse as $fileToAnalyse) {
			unset(
				$filteredErrors[$fileToAnalyse],
				$filteredLocallyIgnoredErrors[$fileToAnalyse],
				$filteredLinesToIgnore[$fileToAnalyse],
				$filteredUnmatchedLineIgnores[$fileToAnalyse],
				$filteredCollectedDataIndex[$fileToAnalyse],
				$filteredExportedNodes[$fileToAnalyse],
			);
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
			valueDependencies: $valueDependenciesToReturn,
			packageDependencies: $packageDependencies,
			exportedNodes: $filteredExportedNodes,
			cachedExportedNodes: $cachedExportedNodes->only($keptCachedExportedNodes),
			projectExtensionFiles: $data['projectExtensionFiles'],
			currentFileHashes: $currentFileHashes,
			collectedDataRuleResults: $collectedDataRuleResults,
			filesWithCollectedDataRuleErrors: $filteredFilesWithCollectedDataRuleErrors,
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
	 * @param list<string> $files
	 * @param array<string, true> $changedFiles
	 */
	private function areAllChanged(array $files, array $changedFiles): bool
	{
		foreach ($files as $file) {
			if (!array_key_exists($file, $changedFiles)) {
				return false;
			}
		}

		return true;
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
		$doSave = function (array $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, LazyCollectedData $collectedData, ?array $dependencies, ?array $usedTraitDependencies, ?array $valueDependencies, ?array $packageDependencies, callable $getExportedNodes, CachedExportedNodes $cachedExportedNodes, array $projectExtensionFiles, FinalizerResult $finalizerResult) use ($internalErrors, $resultCache, $output, $onlyFiles, $meta): ?LazyCollectedData {
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
			if ($valueDependencies === null) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not saved because of error in value dependencies.');
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

			// fetching the nodes of the non-analysed files costs a parse of each of them, so it waits
			// until the cache is known to be saved - a run on a few files or in editor mode never is
			$exportedNodes = $getExportedNodes();

			// A parse error describes the analysed code, so the result cache is still saved. The file's
			// own result is not cached: its errors carry the exception, and it is re-analysed on every
			// run instead.
			$filesToReanalyse = [];
			$currentFileHashes = $resultCache->getCurrentFileHashes();
			foreach ($errorsByFile as $file => $errors) {
				foreach ($errors as $error) {
					if (!$error->hasNonIgnorableException()) {
						continue;
					}

					// any other exception stops the save, and so does an error reported in a file
					// that is not analysed, because only an analysed file is re-analysed
					if (
						$error->getIdentifier() !== 'phpstan.parse'
						|| !array_key_exists($file, $currentFileHashes)
					) {
						if ($output->isVeryVerbose()) {
							$output->writeLineFormatted(sprintf('Result cache was not saved because of non-ignorable exception: %s', $error->getMessage()));
						}

						return null;
					}

					if (array_key_exists($file, $filesToReanalyse)) {
						continue;
					}

					if ($output->isVeryVerbose()) {
						$output->writeLineFormatted(sprintf('Result cache will re-analyse %s because of non-ignorable exception: %s', $file, $error->getMessage()));
					}

					$filesToReanalyse[$file] = true;
				}
			}
			foreach (array_keys($filesToReanalyse) as $file) {
				unset($errorsByFile[$file]);
			}

			// The files changed during the analysis may have been read in another version than the one
			// their hash and exported nodes describe, and so may the files using them, which are analysed
			// again too.
			$filesChangedDuringAnalysis = $this->getFilesChangedDuringAnalysis($resultCache);
			foreach (array_keys($filesChangedDuringAnalysis) as $file) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted(sprintf('Result cache will re-analyse %s and the files using it because it changed during the analysis.', $file));
				}

				$filesToReanalyse[$file] = true;
			}
			if ($filesChangedDuringAnalysis !== []) {
				foreach ([$dependencies, $usedTraitDependencies] as $fileDependencies) {
					foreach ($fileDependencies as $file => $filesDependedOn) {
						if (!array_key_exists($file, $currentFileHashes)) {
							continue;
						}

						foreach ($filesDependedOn as $fileDependedOn) {
							if (array_key_exists($fileDependedOn, $filesChangedDuringAnalysis)) {
								$filesToReanalyse[$file] = true;
								break;
							}
						}
					}
				}
			}

			// in the order save() writes them, which is the order they are restored in
			$collectedDataRuleResults = $finalizerResult->getCollectedDataRuleResults();
			ksort($collectedDataRuleResults);

			$filesWithCollectedDataRuleErrors = [];
			foreach ($finalizerResult->getCollectorErrors() as $collectorError) {
				$filesWithCollectedDataRuleErrors[$collectorError->getFilePath()] = true;
			}
			ksort($filesWithCollectedDataRuleErrors);
			$filesWithCollectedDataRuleErrors = array_keys($filesWithCollectedDataRuleErrors);

			$stubFiles = $this->getStubFiles();
			if (
				!$resultCache->isFullAnalysis()
				&& $filesChangedDuringAnalysis === []
				&& array_diff($resultCache->getFilesToAnalyse(), array_keys($filesToReanalyse)) === []
				&& $this->restoredCacheUnchanged
				&& $errorsByFile === $resultCache->getErrors()
				&& $locallyIgnoredErrorsByFile === $resultCache->getLocallyIgnoredErrors()
				&& $linesToIgnore === $resultCache->getLinesToIgnore()
				&& $unmatchedLineIgnores === $resultCache->getUnmatchedLineIgnores()
				&& $collectedData->getFresh() === []
				&& $collectedData->getCachedIndex() === $resultCache->getCollectedData()->getCachedIndex()
				&& $collectedDataRuleResults === $resultCache->getCollectedDataRuleResults()
				&& $filesWithCollectedDataRuleErrors === $resultCache->getFilesWithCollectedDataRuleErrors()
				&& $packageDependencies === $resultCache->getPackageDependencies()
				&& $exportedNodes === $resultCache->getExportedNodes()
				&& $cachedExportedNodes->getFiles() === $resultCache->getCachedExportedNodes()->getFiles()
				&& $projectExtensionFiles === $resultCache->getProjectExtensionFiles()
				&& $stubFiles === $this->restoredStubFiles
				&& is_file($this->cacheFilePath)
			) {
				if ($output->isVeryVerbose()) {
					$output->writeLineFormatted('Result cache was not rewritten because it is unchanged.');
				}

				return $collectedData;
			}

			foreach (array_keys($filesToReanalyse) as $file) {
				$currentFileHashes[$file] = self::REANALYSE_FILE_HASH_PREFIX . $currentFileHashes[$file];
			}

			try {
				$savedCollectedData = $this->save($resultCache->getLastFullAnalysisTime(), $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedData, $dependencies, $usedTraitDependencies, $valueDependencies, $packageDependencies, $exportedNodes, $cachedExportedNodes, $projectExtensionFiles, $currentFileHashes, $meta, $stubFiles, $collectedDataRuleResults, $filesWithCollectedDataRuleErrors);
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
			return new ResultCacheProcessResult($analyserResult, function (FinalizerResult $finalizerResult) use ($save, $output, $analyserResult, $projectConfigArray, $doSave, $freshErrorsByFile, $freshLocallyIgnoredErrorsByFile, $freshCollectedData): ?LazyCollectedData {
				if ($save === false) {
					if ($output->isVeryVerbose()) {
						$output->writeLineFormatted('Result cache was not saved because it was not requested.');
					}

					return null;
				}

				$projectExtensionFiles = [];
				if ($analyserResult->getDependencies() !== null) {
					$projectExtensionFiles = $this->getProjectExtensionFiles($projectConfigArray, $analyserResult->getDependencies());
				}

				return $doSave($freshErrorsByFile, $freshLocallyIgnoredErrorsByFile, $analyserResult->getLinesToIgnore(), $analyserResult->getUnmatchedLineIgnores(), $freshCollectedData, $analyserResult->getDependencies(), $analyserResult->getUsedTraitDependencies(), $analyserResult->getValueDependencies(), $analyserResult->getPackageDependencies(), fn (): array => $this->addNonAnalysedExportedNodes($analyserResult->getExportedNodes(), $analyserResult->getDependencies(), $analyserResult->getUsedTraitDependencies(), CachedExportedNodes::createEmpty()), CachedExportedNodes::createEmpty(), $projectExtensionFiles, $finalizerResult);
			});
		}

		$errorsByFile = $this->mergeErrors($resultCache, $freshErrorsByFile);
		$locallyIgnoredErrorsByFile = $this->mergeLocallyIgnoredErrors($resultCache, $freshLocallyIgnoredErrorsByFile);
		$collectedData = $this->mergeCollectedData($resultCache, $freshCollectedData->getFresh());
		$dependencies = $this->mergeDependencies($resultCache->getDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getDependencies());
		$usedTraitDependencies = $this->mergeDependencies($resultCache->getUsedTraitDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getUsedTraitDependencies());
		$valueDependencies = $this->mergeValueDependencies($resultCache->getValueDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getValueDependencies());
		$packageDependencies = $this->mergePackageDependencies($resultCache->getPackageDependencies(), $resultCache->getFilesToAnalyse(), $analyserResult->getPackageDependencies());
		// A re-analysed file with a parse error keeps the symbols it had before, see restore(), so that
		// fixing it re-analyses nothing else when they are the same. But only while no other file is
		// analysed: that file sees the broken file's symbols missing, and its result has to be renewed
		// when they come back, which comparing the fixed file with no symbols does.
		$filesToReplaceExportedNodes = array_fill_keys($resultCache->getFilesToAnalyse(), true);
		$filesWithParseError = [];
		foreach ($freshErrorsByFile as $file => $errors) {
			foreach ($errors as $error) {
				if ($error->getIdentifier() !== 'phpstan.parse' || !$error->hasNonIgnorableException()) {
					continue;
				}

				$filesWithParseError[$file] = true;
			}
		}
		if (array_diff_key($filesToReplaceExportedNodes, $filesWithParseError) === []) {
			$filesToReplaceExportedNodes = [];
		}

		// the re-analysed files take their fresh nodes, what is left of the cached ones stays undecoded
		$cachedExportedNodes = $resultCache->getCachedExportedNodes()->without($filesToReplaceExportedNodes);
		$exportedNodes = $this->addNonAnalysedExportedNodes($this->mergeExportedNodes($resultCache, $analyserResult->getExportedNodes()), $dependencies, $usedTraitDependencies, $cachedExportedNodes);
		$linesToIgnore = $this->mergeLinesToIgnore($resultCache, $analyserResult->getLinesToIgnore());
		$unmatchedLineIgnores = $this->mergeUnmatchedLineIgnores($resultCache, $analyserResult->getUnmatchedLineIgnores());

		$saveCallback = function (FinalizerResult $finalizerResult) use ($save, $resultCache, $projectConfigArray, $doSave, $errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedData, $dependencies, $usedTraitDependencies, $valueDependencies, $packageDependencies, $exportedNodes, $cachedExportedNodes): ?LazyCollectedData {
			if ($save === false) {
				return null;
			}

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
			return $doSave($errorsByFile, $locallyIgnoredErrorsByFile, $linesToIgnore, $unmatchedLineIgnores, $collectedData, $dependencies, $usedTraitDependencies, $valueDependencies, $packageDependencies, static fn (): array => $exportedNodes, $cachedExportedNodes, $projectExtensionFiles, $finalizerResult);
		};

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
			valueDependencies: $valueDependencies,
			packageDependencies: $packageDependencies,
			exportedNodes: $exportedNodes,
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
			workerCount: $analyserResult->getWorkerCount(),
		), $saveCallback);
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
	 * The fresh "analysis" dependents of a re-analysed file replace its cached ones, like in
	 * mergeDependencies(). The "declarations" ones are kept: the file they are about may have been
	 * inferred during the analysis of another file that was not re-analysed (see
	 * ValueDependencyCollector), and one left over only means a file re-analysed needlessly. The
	 * fresh values are the ones the analysis saw.
	 *
	 * @param array<string, array{string, string, string, list<string>, list<string>}> $resultCacheValueDependencies
	 * @param string[] $filesToAnalyse
	 * @param ValueDependencies|null $freshValueDependencies
	 * @return ValueDependencies|null
	 */
	private function mergeValueDependencies(array $resultCacheValueDependencies, array $filesToAnalyse, ?array $freshValueDependencies): ?array
	{
		if ($freshValueDependencies === null) {
			return null;
		}

		$values = [];
		$dependents = [];
		foreach ($resultCacheValueDependencies as $id => [$extensionClass, $key, $value, $analysis, $declarations]) {
			$values[$id] = [$extensionClass, $key, $value];
			foreach (['analysis' => $analysis, 'declarations' => $declarations] as $kind => $dependentFiles) {
				foreach ($dependentFiles as $dependentFile) {
					$dependents[$dependentFile] ??= ['analysis' => [], 'declarations' => []];
					$dependents[$dependentFile][$kind][] = $id;
				}
			}
		}

		foreach ($filesToAnalyse as $file) {
			$files = [$file];
			if (array_key_exists($file, $this->fileReplacements)) {
				$files[] = $this->fileReplacements[$file];
			}
			foreach ($files as $reanalysedFile) {
				if (!array_key_exists($reanalysedFile, $dependents)) {
					continue;
				}

				$dependents[$reanalysedFile]['analysis'] = [];
			}
		}

		$merged = ValueDependencyCollector::merge(['values' => [], 'dependents' => $dependents], $freshValueDependencies);
		$merged['values'] = $freshValueDependencies['values'] + $values;

		return $merged;
	}

	/**
	 * @param list<string> $analysisDependentFiles
	 * @param list<string> $declarationDependentFiles
	 * @param array<string, array{fileHash: string, fileStat?: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}> $invertedDependencies
	 * @param array<string, true> $allAnalysedFiles
	 * @return list<string>
	 */
	private function getValueDependentFilesToAnalyse(array $analysisDependentFiles, array $declarationDependentFiles, array $invertedDependencies, array $allAnalysedFiles): array
	{
		$files = [];
		foreach ($analysisDependentFiles as $file) {
			if (!array_key_exists($file, $allAnalysedFiles)) {
				continue;
			}
			$files[] = $file;
		}

		// What these files declare was derived from the value, and the files depending on them see it
		// without having asked for the value themselves.
		foreach ($declarationDependentFiles as $declaringFile) {
			if (array_key_exists($declaringFile, $allAnalysedFiles)) {
				$files[] = $declaringFile;
			}

			$declaringFileData = $invertedDependencies[$declaringFile] ?? null;
			if ($declaringFileData === null) {
				continue;
			}

			foreach (array_merge($declaringFileData['dependentFiles'], $declaringFileData['usedTraitDependentFiles'] ?? []) as $file) {
				if (!is_file($file)) {
					continue;
				}
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * One entry for each value, with the files depending on it in a table of paths like the
	 * dependency graph's, and the key as its extension stores it.
	 *
	 * @param ValueDependencies $valueDependencies
	 * @return array{paths: list<string>, entries: list<array{string, string, string, list<int>, list<int>}>}
	 */
	private function encodeValueDependencies(array $valueDependencies, ResultCachePathTransformer $transformer): array
	{
		$dependentsById = [];
		foreach ($valueDependencies['dependents'] as $dependentFile => ['analysis' => $analysis, 'declarations' => $declarations]) {
			foreach ($analysis as $id) {
				$dependentsById[$id]['analysis'][] = $dependentFile;
			}
			foreach ($declarations as $id) {
				$dependentsById[$id]['declarations'][] = $dependentFile;
			}
		}
		ksort($dependentsById);

		$ids = [];
		$paths = [];
		$entries = [];
		foreach ($dependentsById as $id => $dependents) {
			if (!array_key_exists($id, $valueDependencies['values'])) {
				continue;
			}

			[$extensionClass, $key, $value] = $valueDependencies['values'][$id];
			$extension = $this->valueDependencyCollector->getExtension($extensionClass);
			if ($extension === null) {
				continue;
			}

			$dependentIds = [];
			foreach (['analysis', 'declarations'] as $kind) {
				$pathIds = [];
				$dependentFiles = array_values(array_unique($dependents[$kind] ?? []));
				sort($dependentFiles);
				foreach ($dependentFiles as $dependentFile) {
					if (!isset($ids[$dependentFile])) {
						$ids[$dependentFile] = count($paths);
						$paths[] = $dependentFile;
					}
					$pathIds[] = $ids[$dependentFile];
				}
				$dependentIds[$kind] = $pathIds;
			}

			$entries[] = [$extensionClass, $extension->keyToResultCache($key), $value, $dependentIds['analysis'], $dependentIds['declarations']];
		}

		$relativePaths = [];
		foreach ($paths as $path) {
			$relativePaths[] = $transformer->relativizePath($path);
		}

		return ['paths' => $relativePaths, 'entries' => $entries];
	}

	/**
	 * @param mixed $encoded
	 * @return list<array{extensionClass: string, storedKey: string, value: string, analysis: list<string>, declarations: list<string>}>
	 */
	private function decodeValueDependencies($encoded, ResultCachePathTransformer $transformer): array
	{
		if (!is_array($encoded) || !is_array($encoded['paths'] ?? null) || !is_array($encoded['entries'] ?? null)) {
			throw new RuntimeException('The value dependencies are malformed.');
		}

		$paths = [];
		foreach ($encoded['paths'] as $path) {
			$paths[] = $transformer->absolutizePath($path);
		}

		$valueDependencies = [];
		foreach ($encoded['entries'] as [$extensionClass, $storedKey, $value, $analysisIds, $declarationIds]) {
			$analysis = [];
			foreach ($analysisIds as $pathId) {
				$analysis[] = $paths[$pathId];
			}
			$declarations = [];
			foreach ($declarationIds as $pathId) {
				$declarations[] = $paths[$pathId];
			}
			$valueDependencies[] = [
				'extensionClass' => $extensionClass,
				'storedKey' => $storedKey,
				'value' => $value,
				'analysis' => $analysis,
				'declarations' => $declarations,
			];
		}

		return $valueDependencies;
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
	 * @param ValueDependencies $valueDependencies
	 * @param array<string, array<string>> $packageDependencies
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 * @param array<string, array{string, bool, string}> $projectExtensionFiles
	 * @param array<string, string> $currentFileHashes
	 * @param mixed[] $meta
	 * @param array<string, string> $stubFiles
	 * @param array<string, CollectedDataRuleResult> $collectedDataRuleResults
	 * @param list<string> $filesWithCollectedDataRuleErrors
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
		array $valueDependencies,
		array $packageDependencies,
		array $exportedNodes,
		CachedExportedNodes $cachedExportedNodes,
		array $projectExtensionFiles,
		array $currentFileHashes,
		array $meta,
		array $stubFiles,
		array $collectedDataRuleResults,
		array $filesWithCollectedDataRuleErrors,
	): LazyCollectedData
	{
		$invertedDependencies = [];
		$filesNoOneIsDependingOn = array_fill_keys(array_keys($dependencies), true);
		foreach ($dependencies as $file => $fileDependencies) {
			foreach ($fileDependencies as $fileDep) {
				if (!array_key_exists($fileDep, $invertedDependencies)) {
					$invertedDependencies[$fileDep] = $this->createDependencyEntry($fileDep, $currentFileHashes);
					unset($filesNoOneIsDependingOn[$fileDep]);
				}
				$invertedDependencies[$fileDep]['dependentFiles'][] = $file;
			}
		}

		foreach ($usedTraitDependencies as $file => $fileUsedTraitDependencies) {
			foreach ($fileUsedTraitDependencies as $usedTraitFileDep) {
				if (!array_key_exists($usedTraitFileDep, $invertedDependencies)) {
					$invertedDependencies[$usedTraitFileDep] = $this->createDependencyEntry($usedTraitFileDep, $currentFileHashes) + ['usedTraitDependentFiles' => []];
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

			$invertedDependencies[$file] = $this->createDependencyEntry($file, $currentFileHashes);
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
		ksort($collectedDataRuleResults);

		// The only point where the StubFilesExtensions may run: the analysis is over, bootstrapFiles
		// have been executed, so the extensions can rely on them. restore() reads these hashes back
		// instead of running the extensions again.
		$meta['stubFiles'] = $stubFiles;

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
		$dependencyGraph = $this->encodeDependencyGraph($invertedDependencies, $transformer);
		$encodedValueDependencies = $this->encodeValueDependencies($valueDependencies, $transformer);
		unset($invertedDependencies);
		$packageDependencies = $transformer->relativizeFileKeyed($packageDependencies);
		$projectExtensionFiles = $transformer->relativizeFileKeyed($projectExtensionFiles);
		$collectedDataRuleResults = $transformer->relativizeCollectedDataRuleResults($collectedDataRuleResults);
		$filesWithCollectedDataRuleErrors = array_map(static fn (string $path): string => $transformer->relativizePath($path), $filesWithCollectedDataRuleErrors);

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
			$this->writeValueFrame($handle, $file, 'collectedDataRuleResults', $collectedDataRuleResults);
			$this->writeValueFrame($handle, $file, 'filesWithCollectedDataRuleErrors', $filesWithCollectedDataRuleErrors);
			// An older PHPStan reading this file absolutizes the dependencies before it gets to the
			// cacheVersion check that makes it discard the file, and fails on a missing section.
			$this->writeArrayFrame($handle, $file, 'dependencies', []);
			$this->writeValueFrame($handle, $file, 'dependencyGraph', $dependencyGraph);
			$this->writeValueFrame($handle, $file, 'valueDependencies', $encodedValueDependencies);
			$this->writeArrayFrame($handle, $file, 'packageDependencies', $packageDependencies);
			$this->writeExportedNodes($handle, $file, $exportedNodes, $cachedExportedNodes, $transformer);
			fclose($handle);
			$closed = true;

			// the old file is where the cached exported nodes were copied from, and Windows does not
			// replace a file that is still open
			$cachedExportedNodes->close();

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
					$this->writeCollectedDataEntry($handle, $file, $relativeFile, $data, $collectedDataPerFile);
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
	 * An entry frame whose value is not one serialized blob: a header line with each collector type,
	 * the length of its data and the hash of its values, then the data of each collector, serialized
	 * separately. A rule that asks for one collector reads only that collector's bytes, and the reuse
	 * of rule errors reads only the header line.
	 *
	 * The hash is of the values as they are in memory, with absolute paths, so that a cached file and
	 * a re-analysed one with the same data hash the same.
	 *
	 * @param resource $handle
	 * @param array<string, list<mixed>> $dataPerCollector with paths relative, as stored
	 * @param array<string, list<mixed>> $dataInMemory with paths absolute
	 */
	private function writeCollectedDataEntry($handle, string $file, string $relativeFile, array $dataPerCollector, array $dataInMemory): void
	{
		$transformer = $this->getPathTransformer();
		$header = [];
		$blobs = [];
		foreach ($dataPerCollector as $collectorType => $values) {
			$blob = serialize($values);
			$hash = $transformer->hasPathsInCollectedData($collectorType) ? md5(serialize($dataInMemory[$collectorType])) : md5($blob);
			$header[] = $collectorType . ' ' . strlen($blob) . ' ' . $hash;
			$blobs[] = $blob;
		}

		$keyBlob = serialize($relativeFile);
		$value = implode(' ', $header) . "\n" . implode('', $blobs);
		$this->writeToHandle($handle, $file, strlen($keyBlob) . ' ' . strlen($value) . "\n");
		$this->writeToHandle($handle, $file, $keyBlob);
		$this->writeToHandle($handle, $file, $value);
	}

	/**
	 * The dependency graph names every file once in a path table and refers to it by position.
	 * Written out entry by entry with the paths spelled in full, as it used to be, the graph repeats
	 * each file in the dependent lists of everything it depends on - on Drupal core half a million
	 * paths, 42 MB, each of them rewritten between absolute and relative on every save and restore.
	 * Now it is the 11k paths of the table, and the lists are integers.
	 *
	 * @param array<string, array{fileHash: string, fileStat?: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}> $invertedDependencies
	 * @return array{paths: list<string>, entries: list<array{int, string, string|null, list<int>, list<int>|null}>}
	 */
	private function encodeDependencyGraph(array $invertedDependencies, ResultCachePathTransformer $transformer): array
	{
		$ids = [];
		$paths = [];
		$entries = [];
		foreach ($invertedDependencies as $file => $fileData) {
			if (!isset($ids[$file])) {
				$ids[$file] = count($paths);
				$paths[] = $file;
			}

			$dependentIds = [];
			foreach ($fileData['dependentFiles'] as $dependentFile) {
				if (!isset($ids[$dependentFile])) {
					$ids[$dependentFile] = count($paths);
					$paths[] = $dependentFile;
				}
				$dependentIds[] = $ids[$dependentFile];
			}

			$usedTraitDependentIds = null;
			if (array_key_exists('usedTraitDependentFiles', $fileData)) {
				$usedTraitDependentIds = [];
				foreach ($fileData['usedTraitDependentFiles'] as $dependentFile) {
					if (!isset($ids[$dependentFile])) {
						$ids[$dependentFile] = count($paths);
						$paths[] = $dependentFile;
					}
					$usedTraitDependentIds[] = $ids[$dependentFile];
				}
			}

			$entries[] = [$ids[$file], $fileData['fileHash'], $fileData['fileStat'] ?? null, $dependentIds, $usedTraitDependentIds];
		}

		$relativePaths = [];
		foreach ($paths as $path) {
			$relativePaths[] = $transformer->relativizePath($path);
		}

		return ['paths' => $relativePaths, 'entries' => $entries];
	}

	/**
	 * @param mixed $dependencyGraph
	 * @return array<string, array{fileHash: string, fileStat?: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}>
	 */
	private function decodeDependencyGraph($dependencyGraph, ResultCachePathTransformer $transformer): array
	{
		if (!is_array($dependencyGraph) || !is_array($dependencyGraph['paths'] ?? null) || !is_array($dependencyGraph['entries'] ?? null)) {
			throw new RuntimeException('The dependency graph is malformed.');
		}

		$paths = [];
		foreach ($dependencyGraph['paths'] as $path) {
			$paths[] = $transformer->absolutizePath($path);
		}

		$dependencies = [];
		foreach ($dependencyGraph['entries'] as [$fileId, $fileHash, $fileStat, $dependentIds, $usedTraitDependentIds]) {
			$dependentFiles = [];
			foreach ($dependentIds as $dependentId) {
				$dependentFiles[] = $paths[$dependentId];
			}

			$entry = ['fileHash' => $fileHash, 'dependentFiles' => $dependentFiles];
			if ($fileStat !== null) {
				$entry['fileStat'] = $fileStat;
			}
			if ($usedTraitDependentIds !== null) {
				$usedTraitDependentFiles = [];
				foreach ($usedTraitDependentIds as $dependentId) {
					$usedTraitDependentFiles[] = $paths[$dependentId];
				}
				$entry['usedTraitDependentFiles'] = $usedTraitDependentFiles;
			}

			$dependencies[$paths[$fileId]] = $entry;
		}

		return $dependencies;
	}

	/**
	 * The exported nodes, as an index of the files and the lengths of their serialized nodes, followed
	 * by the serialized nodes back to back - so that the next run can find the few it needs without
	 * decoding the rest, and pass the rest on by copying their bytes, as this does with the ones it
	 * did not decode.
	 *
	 * @param resource $handle
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 */
	private function writeExportedNodes($handle, string $file, array $exportedNodes, CachedExportedNodes $cachedExportedNodes, ResultCachePathTransformer $transformer): void
	{
		$serializedNodes = [];
		foreach ($exportedNodes as $exportedNodesFile => $fileExportedNodes) {
			$serializedNodes[$exportedNodesFile] = serialize($fileExportedNodes);
		}

		$files = [];
		foreach ($cachedExportedNodes->getFiles() as $cachedFile) {
			$files[$cachedFile] = true;
		}
		foreach (array_keys($serializedNodes) as $exportedNodesFile) {
			$files[$exportedNodesFile] = true;
		}
		ksort($files);

		$index = [];
		$size = 0;
		foreach (array_keys($files) as $exportedNodesFile) {
			$length = array_key_exists($exportedNodesFile, $serializedNodes)
				? strlen($serializedNodes[$exportedNodesFile])
				: $cachedExportedNodes->getLength($exportedNodesFile);
			$index[] = [$transformer->relativizePath($exportedNodesFile), $length];
			$size += $length;
		}

		$this->writeValueFrame($handle, $file, 'exportedNodesIndex', $index);
		$this->writeToHandle($handle, $file, 'exportedNodes# ' . $size . "\n");

		// The files keep their order from one cache file to the next, so the cached nodes come in
		// long runs stored one after another in the old file - each run is copied in a few large
		// reads instead of one per file.
		$runOffset = null;
		$runLength = 0;
		foreach (array_keys($files) as $exportedNodesFile) {
			if (array_key_exists($exportedNodesFile, $serializedNodes)) {
				$this->copyCachedExportedNodesRun($handle, $file, $cachedExportedNodes, $runOffset, $runLength);
				$runOffset = null;
				$runLength = 0;
				$this->writeToHandle($handle, $file, $serializedNodes[$exportedNodesFile]);
				continue;
			}

			$offset = $cachedExportedNodes->getOffset($exportedNodesFile);
			$length = $cachedExportedNodes->getLength($exportedNodesFile);
			if ($runOffset !== null && $runOffset + $runLength === $offset) {
				$runLength += $length;
				continue;
			}

			$this->copyCachedExportedNodesRun($handle, $file, $cachedExportedNodes, $runOffset, $runLength);
			$runOffset = $offset;
			$runLength = $length;
		}

		$this->copyCachedExportedNodesRun($handle, $file, $cachedExportedNodes, $runOffset, $runLength);
	}

	/**
	 * @param resource $handle
	 */
	private function copyCachedExportedNodesRun($handle, string $file, CachedExportedNodes $cachedExportedNodes, ?int $offset, int $length): void
	{
		if ($offset === null) {
			return;
		}

		$chunkSize = 8 * 1024 * 1024;
		for ($copied = 0; $copied < $length; $copied += $chunkSize) {
			$chunkLength = min($chunkSize, $length - $copied);
			if ($chunkLength <= 0) {
				break;
			}

			$this->writeToHandle($handle, $file, $cachedExportedNodes->readRange($offset + $copied, $chunkLength));
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
				if (str_ends_with($name, '#')) {
					// payloads stored back to back, their paths and lengths in the index frame before them
					$name = substr($name, 0, -1);
					$size = (int) $size;
					$offset = ftell($handle);
					$index = $data[$name . 'Index'] ?? null;
					if ($offset === false || !is_array($index)) {
						throw new RuntimeException(sprintf('Section "%s" has no index.', $name));
					}

					$locations = [];
					$position = $offset;
					$count = count($index);
					foreach ($index as $i => [$indexedFile, $length]) {
						if (!is_string($indexedFile) || !is_int($length) || $length <= 0) {
							throw new RuntimeException(sprintf('Section "%s" has a malformed index.', $name));
						}

						$locations[$indexedFile] = [$position, $length];
						$position += $length;
						if ($position > $fileSize) {
							throw new RuntimeException(sprintf('Section "%s" is truncated at entry %d of %d.', $name, $i, $count));
						}
					}
					if ($position !== $offset + $size || fseek($handle, $size, SEEK_CUR) !== 0) {
						throw new RuntimeException(sprintf('Section "%s" does not match its index.', $name));
					}

					unset($data[$name . 'Index']);
					$data[$name . 'Locations'] = $locations;
					continue;
				}

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

			$data['cacheFileHandle'] = $handle;
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

	private function createCollectedDataReader(): CollectedDataReader
	{
		return new ResultCacheCollectedDataReader(
			fn (array $index): array => $this->readCollectedData($index),
			fn (array $index): CollectorLocations => $this->locateCollectedDataCollectors($index),
			fn (string $collectorType, array $positions, string $signature): Generator => $this->readCollectedDataCollector($collectorType, $positions, $signature),
		);
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

		$handle = $this->openCacheFileForCollectedData();
		$data = [];
		try {
			foreach ($index as $file => [$offset]) {
				[$key, $valueLength] = $this->seekToEntry($handle, $offset, $file);
				$data[$key] = [];
				foreach ($this->readCollectorHeader($handle, $valueLength, $file) as $collectorType => [$length]) {
					$values = $this->readFrame($handle, $length);
					if (!is_array($values) || !array_is_list($values)) {
						throw new RuntimeException(sprintf('The collected data of %s is not a list.', $file));
					}

					$data[$key][$collectorType] = $values;
				}
			}
		} catch (Throwable $e) {
			throw $this->discardUnreadableCollectedData($handle, $e);
		}

		fclose($handle);

		return $this->getPathTransformer()->absolutizeCollectedData($data);
	}

	/**
	 * @param array<string, array{int, int}> $index
	 */
	private function locateCollectedDataCollectors(array $index): CollectorLocations
	{
		uasort($index, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

		$handle = $this->openCacheFileForCollectedData();
		$positions = [];
		$hashes = [];
		try {
			$signature = $this->getCacheFileSignature($handle);
			foreach ($index as $file => [$offset]) {
				[, $valueLength] = $this->seekToEntry($handle, $offset, $file);
				$collectors = $this->readCollectorHeader($handle, $valueLength, $file);
				$position = ftell($handle);
				if ($position === false) {
					throw new RuntimeException(sprintf('Cannot tell the position of the collected data of %s.', $file));
				}

				foreach ($collectors as $collectorType => [$length, $hash]) {
					$positions[$collectorType][$file] = [$position, $length];
					$hashes[$collectorType][$file] = $hash;
					$position += $length;
				}
			}
		} catch (Throwable $e) {
			throw $this->discardUnreadableCollectedData($handle, $e);
		}

		fclose($handle);

		return new CollectorLocations($signature, $positions, $hashes);
	}

	/**
	 * @param array<string, array{int, int}> $positions
	 * @return Generator<string, list<mixed>>
	 */
	private function readCollectedDataCollector(string $collectorType, array $positions, string $signature): Generator
	{
		$transformer = $this->getPathTransformer();
		$handle = $this->openCacheFileForCollectedData();
		$discarded = false;
		try {
			$this->checkCacheFileSignature($handle, $signature);
			foreach ($positions as $file => [$offset, $length]) {
				$values = $this->readCollectorValues($handle, $file, $offset, $length);
				yield $file => $transformer->absolutizeCollectorValues($collectorType, $values);
			}
		} catch (Throwable $e) {
			$discarded = true;

			throw $this->discardUnreadableCollectedData($handle, $e);
		} finally {
			// also when the caller stops iterating early
			if (!$discarded) {
				fclose($handle);
			}
		}
	}

	/**
	 * The header line of a collected data entry: each collector type, the length of its data and the
	 * hash of its values. Leaves the handle at the data of the first collector.
	 *
	 * @param resource $handle
	 * @return array<class-string<Collector<Node, mixed>>, array{int, string}> collector type => [length of its data, hash]
	 */
	private function readCollectorHeader($handle, int $valueLength, string $file): array
	{
		$line = fgets($handle);
		if ($line === false || !str_ends_with($line, "\n")) {
			throw new RuntimeException(sprintf('The collected data of %s has no header.', $file));
		}

		$collectors = [];
		$total = strlen($line);
		$parts = explode(' ', rtrim($line, "\n"));
		if ($parts !== ['']) {
			$count = count($parts);
			if ($count % 3 !== 0) {
				throw new RuntimeException(sprintf('The collected data of %s has a malformed header.', $file));
			}

			for ($i = 0; $i < $count; $i += 3) {
				$length = (int) $parts[$i + 1];
				$hash = $parts[$i + 2];
				if ($length <= 0 || strlen($hash) !== 32) {
					throw new RuntimeException(sprintf('The collected data of %s has a malformed header.', $file));
				}

				/** @var class-string<Collector<Node, mixed>> $collectorType */
				$collectorType = $parts[$i];
				if (array_key_exists($collectorType, $collectors)) {
					throw new RuntimeException(sprintf('The collected data of %s has a malformed header.', $file));
				}

				$collectors[$collectorType] = [$length, $hash];
				$total += $length;
			}
		}

		if ($total !== $valueLength) {
			throw new RuntimeException(sprintf('The collected data of %s does not match its header.', $file));
		}

		return $collectors;
	}

	/**
	 * @param resource $handle
	 * @return list<mixed>
	 */
	private function readCollectorValues($handle, string $file, int $offset, int $length): array
	{
		$this->seekToCollectorValues($handle, $file, $offset);
		$values = $this->readFrame($handle, $length);
		if (!is_array($values) || !array_is_list($values)) {
			throw new RuntimeException(sprintf('The collected data of %s is not a list.', $file));
		}

		return $values;
	}

	/**
	 * @param resource $handle
	 */
	private function seekToCollectorValues($handle, string $file, int $offset): void
	{
		if (fseek($handle, $offset) !== 0) {
			throw new RuntimeException(sprintf('Cannot seek to the collected data of %s.', $file));
		}
	}

	/**
	 * Identifies the cache file the collector positions were read from. Another run sharing the
	 * tmpDir may rename a different cache over it; the positions are not valid in that one.
	 *
	 * @param resource $handle
	 */
	private function getCacheFileSignature($handle): string
	{
		$stat = fstat($handle);
		if ($stat === false) {
			throw new RuntimeException(sprintf('Cannot stat the result cache file %s.', $this->cacheFilePath));
		}

		return $stat['ino'] . '-' . $stat['size'] . '-' . $stat['mtime'];
	}

	/**
	 * @param resource $handle
	 */
	private function checkCacheFileSignature($handle, string $signature): void
	{
		if ($this->getCacheFileSignature($handle) !== $signature) {
			throw new RuntimeException(sprintf('The result cache file %s changed while it was being read.', $this->cacheFilePath));
		}
	}

	/**
	 * @return resource
	 */
	private function openCacheFileForCollectedData()
	{
		try {
			return $this->openCacheFile();
		} catch (Throwable $e) {
			throw $this->discardUnreadableCollectedData(null, $e);
		}
	}

	/**
	 * The walk in readCacheFile() validated the framing, not the values, so reading them is the first
	 * point where a damaged value shows, and save() has already carried it over into the new file.
	 * Left in place it would fail every run from now on; restore() discards a cache it cannot read
	 * back the same way.
	 *
	 * @param resource|null $handle
	 */
	private function discardUnreadableCollectedData($handle, Throwable $e): UnreadableCollectedDataException
	{
		if ($handle !== null) {
			fclose($handle);
		}

		@unlink($this->cacheFilePath);

		return new UnreadableCollectedDataException(sprintf('The result cache file %s could not be read back and was discarded, so the next run analyses everything: %s', $this->cacheFilePath, $e->getMessage()), previous: $e);
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
		$blob = $this->readBlob($handle, $length);
		$value = @unserialize($blob);
		if ($value === false) {
			throw new RuntimeException(sprintf('A %d byte frame could not be unserialized.', $length));
		}

		return $value;
	}

	/**
	 * @param resource $handle
	 */
	private function readBlob($handle, int $length): string
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

		return $blob;
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
		// Directories from the attributeServicesDirectories section register services straight from
		// their classes, so a changed package owning one of them can affect the analysis of every file.
		foreach ($this->attributeServicesDirectories as $attributeServicesDirectory) {
			$package = $this->packageDependencyResolver->resolveDirectoryPackage($attributeServicesDirectory);
			if ($package !== null && array_key_exists($package, $changedPackagesLookup)) {
				return true;
			}
		}

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
		$classes = $this->attributeServicesClasses;
		if ($projectConfig !== null) {
			$classes = array_merge(ProjectConfigHelper::getServiceClassNames($projectConfig), $classes);
		}
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
				if ($this->packageDependencyResolver->isFileOfInstalledPackage($fileName)) {
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
	private function addNonAnalysedExportedNodes(array $exportedNodes, ?array $dependencies, ?array $usedTraitDependencies, CachedExportedNodes $cachedExportedNodes): array
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
						|| $cachedExportedNodes->has($dependencyFile)
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
			'extensionVersions' => $this->extensionVersionProvider->getExtensionVersions(),
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
	/**
	 * @param array<string, string> $currentFileHashes
	 * @return array{fileHash: string, fileStat?: string, dependentFiles: list<string>}
	 */
	private function createDependencyEntry(string $file, array $currentFileHashes): array
	{
		$entry = [
			'fileHash' => $currentFileHashes[$file] ?? $this->getDependencyFileHash($file),
			'dependentFiles' => [],
		];
		if (
			array_key_exists($file, $currentFileHashes)
			&& !str_starts_with($currentFileHashes[$file], self::REANALYSE_FILE_HASH_PREFIX)
			&& array_key_exists($file, $this->recordedFileStats)
		) {
			$entry['fileStat'] = $this->recordedFileStats[$file];
		}

		return $entry;
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @return array<string, array<int|string, int>> the analysed files that exist, with their stat
	 */
	private function statAnalysedFiles(array $allAnalysedFiles): array
	{
		$stats = [];
		foreach ($allAnalysedFiles as $analysedFile) {
			$stat = @stat($analysedFile);
			// what is_file() tells: it exists and is a regular file, symlinks followed
			if ($stat === false || ($stat['mode'] & 0170000) !== 0100000) {
				continue;
			}

			$stats[$analysedFile] = $stat;
		}

		return $stats;
	}

	/**
	 * Hashing every analysed file is the bulk of what a run with nothing to re-analyse costs - on
	 * Drupal core almost a second for 11k files. A file whose stat signature is what it was when it
	 * was last hashed has not been written to since, so the hash the cache recorded then is reused
	 * - see FileStatSignatures.
	 *
	 * @param array<string, array<int|string, int>> $analysedFileStats
	 * @param array<string, array{fileHash: string, fileStat?: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}>|null $cachedDependencies
	 * @return array<string, string>
	 */
	private function hashAnalysedFiles(array $analysedFileStats, ?array $cachedDependencies): array
	{
		$hashedAt = time();
		$this->hashedFileStats = [];
		$this->racilyHashedFiles = [];
		foreach ($analysedFileStats as $file => $stat) {
			$this->hashedFileStats[$file] = self::getStatKey($stat);
			if ($stat['mtime'] < $hashedAt && $stat['ctime'] < $hashedAt) {
				continue;
			}

			$this->racilyHashedFiles[$file] = true;
		}
		$signatures = $this->fileStatSignatures->begin();
		$hashes = [];
		foreach ($analysedFileStats as $file => $stat) {
			$signature = $signatures->fromStat($stat);
			$cachedEntry = $cachedDependencies[$file] ?? null;
			if (
				$signature !== null
				&& $cachedEntry !== null
				&& ($cachedEntry['fileStat'] ?? null) === $signature
				&& $cachedEntry['fileHash'] !== self::MISSING_FILE_HASH
				&& !array_key_exists($file, $this->fileReplacements)
			) {
				$hash = $cachedEntry['fileHash'];
				$this->fileHashes[$file] = $hash;
			} else {
				$hash = $this->getFileHash($file);
			}

			$hashes[$file] = $hash;
			if ($signature === null) {
				continue;
			}

			$this->recordedFileStats[$file] = $signature;
		}

		return $hashes;
	}

	/**
	 * Whether a signature recorded by hashAnalysedFiles() differs from the one the restored cache
	 * holds for the file, which makes the cache worth rewriting even when nothing else changed.
	 *
	 * @param array<string, array{fileHash: string, fileStat?: string, dependentFiles: list<string>, usedTraitDependentFiles?: list<string>}> $cachedDependencies
	 */
	private function fileStatSignaturesDiffer(array $cachedDependencies): bool
	{
		foreach ($this->recordedFileStats as $file => $signature) {
			if (!array_key_exists($file, $cachedDependencies)) {
				continue;
			}

			if (str_starts_with($cachedDependencies[$file]['fileHash'], self::REANALYSE_FILE_HASH_PREFIX)) {
				continue;
			}

			if (($cachedDependencies[$file]['fileStat'] ?? null) !== $signature) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The analysed files written to since restore() hashed them, for a result that may not describe
	 * what they contain now. A run that analysed nothing read nothing.
	 *
	 * @return array<string, true>
	 */
	public function getFilesChangedDuringAnalysis(ResultCache $resultCache): array
	{
		if (!$resultCache->isFullAnalysis() && $resultCache->getFilesToAnalyse() === []) {
			return [];
		}

		return $this->getFilesChangedSinceHashed($resultCache->getCurrentFileHashes());
	}

	/**
	 * The analysed files written to after they were hashed. The analysis may have read them before
	 * or after the write, or the files depending on them may have read the other version, so neither
	 * the hash recorded for them nor their exported nodes describe what was analysed. That includes
	 * a file changed and changed back, whose hash is the recorded one again.
	 *
	 * A file still written to in the second it was hashed in can keep its stat, its hash tells
	 * whether it is the same - unless it got its hashed contents back within that second as well.
	 *
	 * @param array<string, string> $currentFileHashes
	 * @return array<string, true>
	 */
	private function getFilesChangedSinceHashed(array $currentFileHashes): array
	{
		clearstatcache();
		$changedFiles = [];
		foreach ($this->hashedFileStats as $file => $hashedStat) {
			$stat = @stat($file);
			if ($stat === false) {
				// the next run finds it gone and analyses the files that depended on it
				continue;
			}

			if (self::getStatKey($stat) !== $hashedStat) {
				$changedFiles[$file] = true;
				continue;
			}

			if (!array_key_exists($file, $this->racilyHashedFiles)) {
				continue;
			}

			if (!array_key_exists($file, $currentFileHashes) || hash_file('sha256', $file) === $currentFileHashes[$file]) {
				continue;
			}

			$changedFiles[$file] = true;
		}

		return $changedFiles;
	}

	/**
	 * @param array<int|string, int> $stat what stat() returned for the file
	 */
	private static function getStatKey(array $stat): string
	{
		return sprintf('%d:%d:%d:%d:%d', $stat['size'], $stat['mtime'], $stat['ctime'], $stat['ino'], $stat['dev']);
	}

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
		foreach ($this->scanFileFinder->findFilesCached($directories)->getFiles() as $file) {
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
