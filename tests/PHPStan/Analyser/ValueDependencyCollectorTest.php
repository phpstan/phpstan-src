<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\Analyser\ValueDependencyCollectorTest\TestValueExtension;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use function array_merge;

final class ValueDependencyCollectorTest extends PHPStanTestCase
{

	public function testDedupAndDependents(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$extension = self::getContainer()->getByType(TestValueExtension::class);
		// the container is shared with the other tests, which ask the extension too
		$callsBefore = $extension->calls;
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$analysedFileScope = $scopeFactory->create(ScopeContext::create('/project/src/Analysed.php'));
		$otherFileScope = $scopeFactory->create(ScopeContext::create('/project/src/Other.php'));

		$collector->startFile('/project/src/Analysed.php');
		$collector->record(TestValueExtension::class, 'a', $analysedFileScope, true);
		// the same value again - from another rule or extension - is the same dependency
		$collector->record(TestValueExtension::class, 'a', $analysedFileScope, true);
		$collector->record(TestValueExtension::class, 'b', $otherFileScope, false);

		$a = ValueDependencyCollector::getId(TestValueExtension::class, 'a');
		$b = ValueDependencyCollector::getId(TestValueExtension::class, 'b');
		$this->assertSame([
			'values' => [
				$a => [TestValueExtension::class, 'a', 'value of a'],
				$b => [TestValueExtension::class, 'b', 'value of b'],
			],
			'dependents' => [
				'/project/src/Analysed.php' => [
					'analysis' => [$a, $b],
					'declarations' => [],
				],
				'/project/src/Other.php' => [
					'analysis' => [],
					'declarations' => [$b],
				],
			],
		], $collector->finishFile());
		$this->assertSame($callsBefore + 2, $extension->calls);
	}

	public function testFile(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		// the paths FileAnalyser passes along are normalized - with backslashes on Windows
		$analysedFile = $fileHelper->normalizePath(__DIR__ . '/data/value-dependency-analysed.php');
		$dataFile = $fileHelper->normalizePath(__DIR__ . '/data/value-dependency-missing.txt');
		$otherFile = $fileHelper->normalizePath(__DIR__ . '/data/value-dependency-other.php');
		$analysedFileScope = $scopeFactory->create(ScopeContext::create($analysedFile));
		$otherFileScope = $scopeFactory->create(ScopeContext::create($otherFile));

		$collector->startFile($analysedFile);
		// the analysed file is re-analysed when it changes anyway
		$collector->recordFile($analysedFile, $analysedFileScope, true);
		// the same path, written differently, is the same dependency
		$collector->recordFile(__DIR__ . '/data/../data/value-dependency-missing.txt', $analysedFileScope, true);
		$collector->recordFile($dataFile, $analysedFileScope, true);
		// outside the walk, what the other file declares depends on the analysed file
		$collector->recordFile($analysedFile, $otherFileScope, false);

		$dataFileId = ValueDependencyCollector::getId(FileResultCacheValueExtension::class, $dataFile);
		$analysedFileId = ValueDependencyCollector::getId(FileResultCacheValueExtension::class, $analysedFile);
		$this->assertSame([
			'values' => [
				$dataFileId => [FileResultCacheValueExtension::class, $dataFile, 'missing'],
				$analysedFileId => [FileResultCacheValueExtension::class, $analysedFile, 'missing'],
			],
			'dependents' => [
				$analysedFile => [
					'analysis' => [$dataFileId, $analysedFileId],
					'declarations' => [],
				],
				$otherFile => [
					'analysis' => [],
					'declarations' => [$analysedFileId],
				],
			],
		], $collector->finishFile());
	}

	public function testClassDeclarations(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);
		$id = ValueDependencyCollector::getId(TestValueExtension::class, 'declared');

		// recorded while some file is analysed, kept for the rest of the process
		$collector->startFile('/project/src/First.php');
		$collector->recordForClass(PHPStanTestCase::class, TestValueExtension::class, 'declared');
		$this->assertSame([], $collector->finishFile()['dependents']['/project/src/First.php']['analysis']);

		// a later file depending on a subclass gets it, through the ancestors
		$collector->startFile('/project/src/Second.php');
		$collector->noteClassDependency($reflectionProvider->getClass(self::class));
		$this->assertSame([
			'values' => [$id => [TestValueExtension::class, 'declared', 'value of declared']],
			'dependents' => ['/project/src/Second.php' => ['analysis' => [$id], 'declarations' => []]],
		], $collector->finishFile());

		// one not depending on the class does not
		$collector->startFile('/project/src/Third.php');
		$collector->noteClassDependency($reflectionProvider->getClass(ValueDependencyCollector::class));
		$this->assertSame([], $collector->finishFile()['dependents']['/project/src/Third.php']['analysis']);
	}

	public function testNothingOutsideOfAnalysedFile(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('/project/src/Analysed.php'));

		$collector->record(TestValueExtension::class, 'a', $scope, true);
		$collector->startFile('/project/src/Analysed.php');

		$this->assertSame([
			'values' => [],
			'dependents' => [
				'/project/src/Analysed.php' => [
					'analysis' => [],
					'declarations' => [],
				],
			],
		], $collector->finishFile());
	}

	public function testUnregisteredExtension(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('/project/src/Analysed.php'));

		$collector->startFile('/project/src/Analysed.php');
		try {
			$this->expectException(ShouldNotHappenException::class);
			$this->expectExceptionMessage('stdClass is not registered as a result cache value extension. Register it with the phpstan.resultCacheValueExtension service tag.');
			// @phpstan-ignore argument.type (not an extension on purpose)
			$collector->record('stdClass', 'a', $scope, true);
		} finally {
			$collector->finishFile();
		}
	}

	public function testMerge(): void
	{
		$this->assertSame([
			'values' => [
				'x' => ['E', 'x', 'first'],
				'y' => ['E', 'y', 'y'],
			],
			'dependents' => [
				'/a.php' => ['analysis' => ['x', 'y'], 'declarations' => []],
				'/b.php' => ['analysis' => [], 'declarations' => ['x']],
			],
		], ValueDependencyCollector::merge([
			'values' => ['x' => ['E', 'x', 'first']],
			'dependents' => ['/a.php' => ['analysis' => ['x'], 'declarations' => []]],
		], [
			'values' => ['x' => ['E', 'x', 'second'], 'y' => ['E', 'y', 'y']],
			'dependents' => [
				'/a.php' => ['analysis' => ['x', 'y'], 'declarations' => []],
				'/b.php' => ['analysis' => [], 'declarations' => ['x']],
			],
		]));
	}

	#[Override]
	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/value-dependency-collector.neon',
		]);
	}

}
