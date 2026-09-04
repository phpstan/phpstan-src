<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Nette\Utils\Strings;
use Override;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Command\Output;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\File\FileHelper;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle as SymfonyConsoleStyle;
use function array_keys;
use function basename;
use function count;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function md5_file;
use function mkdir;
use function ord;
use function rmdir;
use function strlen;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

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
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));
		$before = md5_file($this->cacheFilePath());

		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$processed = $manager->process($this->analyserResult([]), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->isSaved());
		$this->assertSame($before, md5_file($this->cacheFilePath()));
		$this->assertSame($this->collectedData(['a', 'b', 'c']), $processed->getAnalyserResult()->getCollectedData());
	}

	public function testPartialSaveEqualsFreshSaveOfMergedData(): void
	{
		$this->saveFullAnalysis($this->collectedData(['a', 'b', 'c']));

		file_put_contents($this->files[1], "<?php class Cacheb { public function m(): void {} }\n");
		$manager = $this->createManager();
		$resultCache = $manager->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertSame([$this->files[1]], $resultCache->getFilesToAnalyse());

		$freshB = [$this->files[1] => [MethodWithoutImpurePointsCollector::class => ['fresh b']]];
		$processed = $manager->process($this->analyserResult($freshB), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->isSaved());

		// cached entries come first, the re-analysed file after them
		$expected = $this->collectedData(['a', 'c', 'b']);
		$expected[$this->files[1]] = $freshB[$this->files[1]];
		$this->assertSame($expected, $processed->getAnalyserResult()->getCollectedData());

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
	 * @param array<string, array<class-string<MethodWithoutImpurePointsCollector>, list<string>>> $collectedData
	 */
	private function saveFullAnalysis(array $collectedData): void
	{
		@unlink($this->cacheFilePath());
		$manager = $this->createManager();
		$resultCache = $this->createManager()->restore($this->files, false, false, null, $this->nullOutput());
		$this->assertTrue($resultCache->isFullAnalysis());

		$processed = $manager->process($this->analyserResult($collectedData), $resultCache, $this->nullOutput(), false, true);
		$this->assertTrue($processed->isSaved());
		$this->assertCount(count($collectedData), $processed->getAnalyserResult()->getCollectedData());
	}

	/**
	 * @param array<string, array<class-string<MethodWithoutImpurePointsCollector>, list<string>>> $collectedData
	 */
	private function analyserResult(array $collectedData): AnalyserResult
	{
		$dependencies = [];
		foreach ($this->files as $file) {
			$dependencies[$file] = [];
		}

		return new AnalyserResult(
			unorderedErrors: [],
			filteredPhpErrors: [],
			allPhpErrors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			internalErrors: [],
			collectedData: LazyCollectedData::fromArray($collectedData),
			dependencies: $dependencies,
			usedTraitDependencies: [],
			packageDependencies: [],
			exportedNodes: [],
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
			resultCacheMetaExtensions: $container->getExtensionsCollection(ResultCacheMetaExtension::class),
			exportedNodeFetcher: $container->getByType(ExportedNodeFetcher::class),
			scanFileFinder: $container->getService('fileFinderScan'),
			stubFilesProvider: $container->getByType(StubFilesProvider::class),
			fileHelper: $container->getByType(FileHelper::class),
			packageDependencyResolver: $container->getByType(PackageDependencyResolver::class),
			cacheFilePath: $this->cacheFilePath(),
			analysedPaths: [$this->directory . '/src'],
			analysedPathsFromConfig: [],
			composerAutoloaderProjectPaths: [],
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
		);
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
