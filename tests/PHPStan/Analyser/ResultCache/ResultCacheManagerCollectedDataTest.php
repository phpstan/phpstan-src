<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Nette\Utils\Strings;
use Override;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FinalizerResult;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Collectors\CollectedData;
use PHPStan\Command\Output;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\File\FileHelper;
use PHPStan\File\FileStatSignatures;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use PHPStan\Rules\Comparison\ConstantConditionInTraitCollector;
use PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
use PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector;
use PHPStan\Rules\DeadCode\PossiblyPureNewCollector;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle as SymfonyConsoleStyle;
use function array_keys;
use function basename;
use function clearstatcache;
use function count;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function iterator_to_array;
use function max;
use function md5_file;
use function mkdir;
use function ord;
use function rmdir;
use function stat;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function time;
use function uniqid;
use function unlink;
use function usleep;

/**
 * @phpstan-import-type CollectorData from CollectedData
 */
class ResultCacheManagerCollectedDataTest extends PHPStanTestCase
{

	private string $directory;

	/** @var list<string> */
	private array $files = [];

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$this->directory = $fileHelper->normalizePath(sys_get_temp_dir() . '/phpstan-collected-data-' . uniqid());
		// the cache file must not be in the analysed directory, or it counts as a scanned file
		mkdir($this->directory . '/src', 0777, true);
		foreach (['a', 'b', 'c'] as $name) {
			$file = $fileHelper->normalizePath($this->directory . '/src/' . $name . '.php');
			file_put_contents($file, '<?php class Cache' . $name . " {}\n");
			$this->files[] = $file;
		}
	}

	#[Override]
	protected function tearDown(): void
	{
		parent::tearDown();
		foreach ($this->files as $file) {
			@unlink($file);
		}
		@unlink($this->cacheFilePath());
		@rmdir($this->directory . '/src');
		@rmdir($this->directory);
	}

	public function testCachedEntriesAreIndexedAndReadBack(): void
	{
		$data = $this->collectedData(['a', 'b', 'c']);
		$this->saveFullAnalysis($data);

		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertFalse($resultCache->isFullAnalysis());
		$this->assertSame([], $resultCache->getFilesToAnalyse());

		$lazy = $resultCache->getCollectedData();
		$this->assertSame($this->files, array_keys($lazy->getCachedIndex()));
		$this->assertSame([], $lazy->getFresh());
		$this->assertSame($data, $lazy->toArray());

		$contents = file_get_contents($this->cacheFilePath());
		$this->assertNotFalse($contents);
		foreach ($lazy->getCachedIndex() as $file => [$offset, $length]) {
			$entry = substr($contents, $offset, $length);
			$this->assertStringContainsString('"src/' . basename($file) . '"', $entry);
			$this->assertStringEndsWith('}', $entry);
		}
	}

	public function testWarmSaveCopiesEveryEntryByteForByte(): void
	{
		$this->waitUntilTheFilesAreInThePast();
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));
		$before = md5_file($this->cacheFilePath());

		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$processed = $manager->process($this->analyserResult([]), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->save(new FinalizerResult($processed->getAnalyserResult(), [], [])));
		$this->assertSame($before, md5_file($this->cacheFilePath()));
		$this->assertSame($this->collectedData(['a', 'b', 'c']), $this->savedCollectedData($processed));
	}

	public function testPartialSaveEqualsFreshSaveOfMergedData(): void
	{
		$this->waitUntilTheFilesAreInThePast();
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));

		file_put_contents($this->files[1], "<?php class Cacheb { public function m(): void {} }\n");
		$this->waitUntilTheFilesAreInThePast();
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$this->files[1]], $resultCache->getFilesToAnalyse());

		$freshB = [$this->files[1] => [MethodWithoutImpurePointsCollector::class => ['fresh b']]];
		$processed = $manager->process($this->analyserResult($freshB), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->save(new FinalizerResult($processed->getAnalyserResult(), [], [])));

		// cached entries come first, the re-analysed file after them
		$expected = $this->collectedData(['a', 'c', 'b']);
		$expected[$this->files[1]] = $freshB[$this->files[1]];
		$this->assertSame($expected, $this->savedCollectedData($processed));

		$partial = $this->cacheContentsWithoutTime();
		$this->saveFullAnalysis($expected);
		$this->assertSame($partial, $this->cacheContentsWithoutTime());
	}

	public function testDamagedValueDiscardsTheCacheWhenRead(): void
	{
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));
		$this->breakFirstCollectedDataValue();

		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertFalse($resultCache->isFullAnalysis(), 'the framing is intact, only a value is damaged');

		try {
			$resultCache->getCollectedData()->toArray();
			$this->fail('The damaged value should not decode.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('could not be read back and was discarded', $e->getMessage());
		}

		$this->assertFalse(is_file($this->cacheFilePath()));
	}

	public function testEachCollectorIsReadOnItsOwn(): void
	{
		[$a, $b, $c] = $this->files;
		$data = [
			$a => [MethodWithoutImpurePointsCollector::class => ['method a'], PossiblyPureFuncCallCollector::class => [['callA', 3]]],
			$b => [MethodWithoutImpurePointsCollector::class => ['method b']],
			$c => [PossiblyPureFuncCallCollector::class => [['callC', 4], ['callC2', 5]]],
		];
		$this->saveFullAnalysis($data);

		$lazy = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput())->getCollectedData();
		$this->assertSame([$a => ['method a'], $b => ['method b']], iterator_to_array($lazy->iterate(MethodWithoutImpurePointsCollector::class)));
		$this->assertSame([$a => [['callA', 3]], $c => [['callC', 4], ['callC2', 5]]], iterator_to_array($lazy->iterate(PossiblyPureFuncCallCollector::class)));
		$this->assertSame([], iterator_to_array($lazy->iterate(PossiblyPureNewCollector::class)));
		$this->assertSame($data, $lazy->toArray());

		$node = new CollectedDataNode($lazy, false);
		$this->assertSame([$a => [['callA', 3]], $c => [['callC', 4], ['callC2', 5]]], $node->get(PossiblyPureFuncCallCollector::class));
		$iterated = [];
		foreach ($node->iterate(PossiblyPureFuncCallCollector::class) as $file => $values) {
			$iterated[$file] = $values;
		}
		$this->assertSame([$a => [['callA', 3]], $c => [['callC', 4], ['callC2', 5]]], $iterated);
	}

	public function testCachedAndFreshDataHashTheSame(): void
	{
		[$a, $b, $c] = $this->files;
		$conditionInTrait = [['SomeRule', 'SomeTrait', 'key', true, new Error('An error.', $a, 3)]];
		$data = [
			$a => [
				MethodWithoutImpurePointsCollector::class => ['method a'],
				ConstantConditionInTraitCollector::class => $conditionInTrait,
			],
			$b => [MethodWithoutImpurePointsCollector::class => ['method b']],
			$c => [MethodWithoutImpurePointsCollector::class => ['method c']],
		];
		$this->waitUntilTheFilesAreInThePast();
		$this->saveFullAnalysis($data);

		$cached = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput())->getCollectedData();
		$fresh = LazyCollectedData::fromArray($data);
		foreach ([MethodWithoutImpurePointsCollector::class, ConstantConditionInTraitCollector::class] as $collectorType) {
			$this->assertSame($fresh->hashCollector($collectorType), $cached->hashCollector($collectorType), $collectorType);
		}

		// paths inside the values are stored relative, iterate() makes them absolute again
		$this->assertEquals([$a => $conditionInTrait], iterator_to_array($cached->iterate(ConstantConditionInTraitCollector::class)));

		file_put_contents($b, "<?php class Cacheb { public function m(): void {} }\n");
		$this->waitUntilTheFilesAreInThePast();
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$b], $resultCache->getFilesToAnalyse());
		$partial = $manager->process($this->analyserResult([$b => $data[$b]]), $resultCache, $this->nullOutput(), false, true)
			->getAnalyserResult()
			->getLazyCollectedData();
		$this->assertSame([$b], array_keys($partial->getFresh()));
		$this->assertSame($fresh->hashCollector(MethodWithoutImpurePointsCollector::class), $partial->hashCollector(MethodWithoutImpurePointsCollector::class));
	}

	public function testDamagedValueDiscardsTheCacheWhenIterated(): void
	{
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));
		$this->breakFirstCollectedDataValue();

		$lazy = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput())->getCollectedData();
		try {
			iterator_to_array($lazy->iterate(MethodWithoutImpurePointsCollector::class));
			$this->fail('The damaged value should not decode.');
		} catch (UnreadableCollectedDataException $e) {
			$this->assertStringContainsString('could not be read back and was discarded', $e->getMessage());
		}

		$this->assertFalse(is_file($this->cacheFilePath()));
	}

	public function testDuplicateCollectorInHeaderDiscardsTheCache(): void
	{
		$data = [$this->files[0] => [
			MethodWithoutImpurePointsCollector::class => ['method a'],
			PossiblyPureFuncCallCollector::class => [['callA', 3]],
		]];
		$this->saveFullAnalysis($data);
		$contents = file_get_contents($this->cacheFilePath());
		$this->assertNotFalse($contents);
		// both blobs are under the same collector type, the lengths still add up
		$contents = str_replace(PossiblyPureFuncCallCollector::class . ' ', MethodWithoutImpurePointsCollector::class . ' ', $contents, $count);
		$this->assertSame(1, $count);
		// the type names differ in length, so the entry header must still match the entry
		$entryLength = strlen(PossiblyPureFuncCallCollector::class) - strlen(MethodWithoutImpurePointsCollector::class);
		$contents = Strings::replace($contents, '~^(\d+) (\d+)\n(s:\d+:"src/a\.php";)~m', static fn (array $m): string => $m[1] . ' ' . ((int) $m[2] - $entryLength) . "\n" . $m[3]);
		file_put_contents($this->cacheFilePath(), $contents);

		$lazy = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput())->getCollectedData();
		try {
			iterator_to_array($lazy->iterate(MethodWithoutImpurePointsCollector::class));
			$this->fail('The duplicate collector should be refused.');
		} catch (UnreadableCollectedDataException $e) {
			$this->assertStringContainsString('has a malformed header', $e->getMessage());
		}

		$this->assertFalse(is_file($this->cacheFilePath()));
	}

	public function testTruncatedSectionIsDiscardedOnRestore(): void
	{
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));
		$contents = file_get_contents($this->cacheFilePath());
		$this->assertNotFalse($contents);
		$position = strpos($contents, "collectedData* 3\n");
		$this->assertNotFalse($position);
		file_put_contents($this->cacheFilePath(), substr($contents, 0, $position + strlen("collectedData* 3\n") + 20));

		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertTrue($resultCache->isFullAnalysis());
		$this->assertFalse(is_file($this->cacheFilePath()));
	}

	public function testPreviousEntryFramingIsDiscarded(): void
	{
		$this->saveFullAnalysis($this->collectedData(['a']));
		$contents = file_get_contents($this->cacheFilePath());
		$this->assertNotFalse($contents);

		// the 2.2.13 framing: one length per entry, the key inside the payload
		$position = strpos($contents, "collectedData* 1\n");
		$this->assertNotFalse($position);
		$entryStart = $position + strlen("collectedData* 1\n");
		$headerEnd = strpos($contents, "\n", $entryStart);
		$this->assertNotFalse($headerEnd);
		file_put_contents($this->cacheFilePath(), substr($contents, 0, $entryStart) . '42' . substr($contents, $headerEnd));

		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertTrue($resultCache->isFullAnalysis());
	}

	public function testFileChangedDuringTheAnalysisIsAnalysedAgainWithTheFilesUsingIt(): void
	{
		[$a, $b, $c] = $this->files;
		$dependencies = [$b => [$a]];
		$this->waitUntilTheFilesAreInThePast();
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		// a file with an error unrelated to a, analysed again only when a new symbol appears
		$processed = $manager->process($this->analyserResult([], $dependencies, $this->files, [$c]), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->save(new FinalizerResult($processed->getAnalyserResult(), [], [])));

		file_put_contents($a, "<?php class Cachea { public function m(): int {} }\n");
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$a, $b], $resultCache->getFilesToAnalyse());
		// written after b read it, before a itself was read
		file_put_contents($a, "<?php class Cachea { public function m(): string {} }\n");
		$processed = $manager->process($this->analyserResult([], $dependencies, [$a, $b]), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->save(new FinalizerResult($processed->getAnalyserResult(), [], [])));

		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$a, $b], $resultCache->getFilesToAnalyse());
	}

	public function testFileChangedAndChangedBackDuringTheAnalysisIsAnalysedAgain(): void
	{
		$a = $this->files[0];
		$this->saveFullAnalysis([]);

		$hashed = "<?php class Cachea { public function m(): int {} }\n";
		file_put_contents($a, $hashed);
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$a], $resultCache->getFilesToAnalyse());
		// analysed while it was different, then undone in the editor in a later second
		file_put_contents($a, "<?php class Cachea { public function m(): string {} }\n");
		$this->waitUntilTheFilesAreInThePast();
		file_put_contents($a, $hashed);
		$processed = $manager->process($this->analyserResult([], [], [$a]), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->save(new FinalizerResult($processed->getAnalyserResult(), [], [])));
		$this->assertSame([$a => true], $manager->getFilesChangedDuringAnalysis($resultCache));

		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$a], $resultCache->getFilesToAnalyse());
	}

	public function testNoFilesChangedDuringTheAnalysis(): void
	{
		$a = $this->files[0];
		$this->saveFullAnalysis([]);
		$this->waitUntilTheFilesAreInThePast();

		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([], $resultCache->getFilesToAnalyse());
		// a run that analysed nothing read nothing
		file_put_contents($a, "<?php class Cachea { public function m(): string {} }\n");
		$this->assertSame([], $manager->getFilesChangedDuringAnalysis($resultCache));

		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$a], $resultCache->getFilesToAnalyse());
		$this->assertSame([], $manager->getFilesChangedDuringAnalysis($resultCache));
	}

	/**
	 * A save keeps the stat signature only of a file not modified in the second the save began,
	 * so without waiting, saves on both sides of a second boundary differ in their signatures.
	 */
	private function waitUntilTheFilesAreInThePast(): void
	{
		clearstatcache();
		$latest = 0;
		foreach ($this->files as $file) {
			$stat = stat($file);
			$this->assertIsArray($stat);
			$latest = max($latest, $stat['mtime'], $stat['ctime']);
		}

		while (time() <= $latest) {
			usleep(50_000);
		}
	}

	/**
	 * @param list<string> $names
	 * @return array<string, array<class-string<MethodWithoutImpurePointsCollector>, list<string>>>
	 */
	private function collectedData(array $names): array
	{
		$data = [];
		foreach ($names as $name) {
			$data[$this->files[ord($name) - ord('a')]] = [MethodWithoutImpurePointsCollector::class => ['usage in ' . $name]];
		}

		return $data;
	}

	/**
	 * @param CollectorData $collectedData
	 */
	private function saveFullAnalysis(array $collectedData): void
	{
		@unlink($this->cacheFilePath());
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertTrue($resultCache->isFullAnalysis());

		$processed = $manager->process($this->analyserResult($collectedData), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->save(new FinalizerResult($processed->getAnalyserResult(), [], [])));
		$this->assertCount(count($collectedData), $this->savedCollectedData($processed));
	}

	/**
	 * @param CollectorData $collectedData
	 * @param array<string, list<string>> $dependencies of the files that depend on others
	 * @param list<string> $filesWithExportedNodes analysed files whose exported nodes are read now
	 * @param list<string> $filesWithErrors
	 */
	private function analyserResult(array $collectedData, array $dependencies = [], array $filesWithExportedNodes = [], array $filesWithErrors = []): AnalyserResult
	{
		foreach ($this->files as $file) {
			$dependencies[$file] ??= [];
		}

		$exportedNodeFetcher = self::getContainer()->getByType(ExportedNodeFetcher::class);
		$exportedNodes = [];
		foreach ($filesWithExportedNodes as $file) {
			$exportedNodes[$file] = $exportedNodeFetcher->fetchNodes($file);
		}

		$errors = [];
		foreach ($filesWithErrors as $file) {
			$errors[] = new Error('An error.', $file, 1);
		}

		return new AnalyserResult(
			unorderedErrors: $errors,
			filteredPhpErrors: [],
			allPhpErrors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			internalErrors: [],
			collectedData: LazyCollectedData::fromArray($collectedData),
			dependencies: $dependencies,
			usedTraitDependencies: [],
			valueDependencies: ['values' => [], 'dependents' => []],
			packageDependencies: [],
			exportedNodes: $exportedNodes,
			reachedInternalErrorsCountLimit: false,
			peakMemoryUsageBytes: 0,
			processedFiles: $this->files,
		);
	}

	/**
	 * A full analysis records its own time, which a partial save keeps from the previous one.
	 */
	private function cacheContentsWithoutTime(): string
	{
		$contents = file_get_contents($this->cacheFilePath());
		$this->assertNotFalse($contents);

		return Strings::replace($contents, '~^lastFullAnalysisTime \d+\ni:\d+;~m', '');
	}

	private function breakFirstCollectedDataValue(): void
	{
		$contents = file_get_contents($this->cacheFilePath());
		$this->assertNotFalse($contents);
		$position = strpos($contents, 's:10:"usage in a"');
		$this->assertNotFalse($position);
		// the declared length no longer matches the body, the frame keeps its size
		file_put_contents($this->cacheFilePath(), substr($contents, 0, $position) . 's:11:' . substr($contents, $position + 5));
	}

	private function createManager(): ResultCacheManager
	{
		$container = self::getContainer();

		return new ResultCacheManager(
			resultCacheMetaExtensions: $container->getExtensionsCollection(ResultCacheMetaExtension::class), // @phpstan-ignore classConstant.deprecatedInterface
			exportedNodeFetcher: $container->getByType(ExportedNodeFetcher::class),
			scanFileFinder: $container->getService('fileFinderScan'),
			stubFilesProvider: $container->getByType(StubFilesProvider::class),
			packageDependencyResolver: $container->getByType(PackageDependencyResolver::class),
			extensionVersionProvider: $container->getByType(ExtensionVersionProvider::class),
			cacheFilePath: $this->cacheFilePath(),
			analysedPaths: [$this->directory . '/src'],
			analysedPathsFromConfig: [],
			composerAutoloaderProjectPaths: [],
			attributeServicesDirectories: [],
			attributeServicesClasses: [],
			usedLevel: '8',
			cliAutoloadFile: null,
			bootstrapFiles: [],
			scanFiles: [],
			scanDirectories: [],
			configStubFiles: [],
			fileReplacements: [],
			checkDependenciesOfProjectExtensionFiles: false,
			parametersNotInvalidatingCache: [],
			skipResultCacheIfOlderThanDays: 7,
			anchorDirectory: $this->directory,
			phpVersion: $container->getByType(PhpVersion::class),
			composerPhpVersionFactory: $container->getByType(ComposerPhpVersionFactory::class),
			fileStatSignatures: $container->getByType(FileStatSignatures::class),
			valueDependencyCollector: $container->getByType(ValueDependencyCollector::class),
			composerSourceLocatorMaker: $container->getByType(ComposerJsonAndInstalledJsonSourceLocatorMaker::class),
			tmpDir: $this->directory . '/tmp',
		);
	}

	/**
	 * @return array<mixed>
	 */
	private function savedCollectedData(ResultCacheProcessResult $processed): array
	{
		$collectedData = $processed->getSavedCollectedData();
		$this->assertNotNull($collectedData);

		return $collectedData->toArray();
	}

	private function cacheFilePath(): string
	{
		return $this->directory . '/resultCache.php';
	}

	private function nullOutput(): Output
	{
		$symfonyOutput = new NullOutput();

		return new SymfonyOutput($symfonyOutput, new SymfonyStyle(new SymfonyConsoleStyle(new StringInput(''), $symfonyOutput)));
	}

}
