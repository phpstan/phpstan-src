<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\ResultCacheDependencyCollector;
use PHPStan\Command\Output;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/** @phpstan-import-type CollectorData from CollectedData */
class ResultCacheManagerTest extends PHPStanTestCase
{

	/** @return iterable<string, array{bool, bool}> */
	public static function providePartialCacheSaveModes(): iterable
	{
		yield 'saving disabled' => [false, true];
		yield 'save rejected because dependencies are unavailable' => [true, false];
	}

	#[DataProvider('providePartialCacheSaveModes')]
	public function testProcessDoesNotExposeDependencyHashesWhenPartialCacheIsNotSaved(bool $save, bool $dependenciesAvailable): void
	{
		$file = '/analysed.php';
		$result = $this->createManager()->process(
			$this->createAnalyserResult([], $dependenciesAvailable ? [] : null),
			$this->createResultCache(false, [], [
				$file => [ResultCacheDependencyCollector::class => [[
					'extensionKey' => 'provider',
					'dependencyKey' => 'dependency',
					'hash' => 'internal-cache-hash',
				]]],
			]),
			$this->createStub(Output::class),
			false,
			$save,
		);

		$this->assertFalse($result->isSaved());
		$this->assertSame([
			$file => [ResultCacheDependencyCollector::class => [[
				'extensionKey' => 'provider',
				'dependencyKey' => 'dependency',
			]]],
		], $result->getAnalyserResult()->getCollectedData());
	}

	public function testProcessUsesFreshDependencyRecordInsteadOfMalformedCachedRecord(): void
	{
		$file = '/analysed.php';
		$freshCollectedData = [
			$file => [ResultCacheDependencyCollector::class => [[
				'extensionKey' => 'provider',
				'dependencyKey' => 'fresh-dependency',
			]]],
		];
		$result = $this->createManager()->process(
			$this->createAnalyserResult($freshCollectedData),
			$this->createResultCache(false, [$file], [
				$file => [ResultCacheDependencyCollector::class => [[
					'extensionKey' => 'provider',
					'dependencyKey' => [],
					'hash' => 'internal-cache-hash',
				]]],
			]),
			$this->createStub(Output::class),
			false,
			false,
		);

		$this->assertSame($freshCollectedData, $result->getAnalyserResult()->getCollectedData());
	}

	public function testProcessNormalizesFreshDependencyRecords(): void
	{
		$file = '/analysed.php';
		$result = $this->createManager()->process(
			$this->createAnalyserResult([
				$file => [ResultCacheDependencyCollector::class => [
					[
						'extensionKey' => 'provider',
						'dependencyKey' => 'dependency',
						'hash' => 'extension-supplied',
					],
					[
						'extensionKey' => 'provider',
						'dependencyKey' => 'dependency',
					],
				]],
			]),
			$this->createResultCache(true, [$file], []),
			$this->createStub(Output::class),
			false,
			false,
		);

		$this->assertSame([
			$file => [ResultCacheDependencyCollector::class => [[
				'extensionKey' => 'provider',
				'dependencyKey' => 'dependency',
			]]],
		], $result->getAnalyserResult()->getCollectedData());
	}

	private function createManager(): ResultCacheManager
	{
		return self::getContainer()->getByType(ResultCacheManagerFactory::class)->create([]);
	}

	/**
	 * @param string[] $filesToAnalyse
	 * @param CollectorData $collectedData
	 */
	private function createResultCache(bool $fullAnalysis, array $filesToAnalyse, array $collectedData): ResultCache
	{
		return new ResultCache(
			filesToAnalyse: $filesToAnalyse,
			fullAnalysis: $fullAnalysis,
			lastFullAnalysisTime: 0,
			meta: ['projectConfig' => null],
			errors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			collectedData: $collectedData,
			dependencies: [],
			usedTraitDependencies: [],
			packageDependencies: [],
			exportedNodes: [],
			projectExtensionFiles: [],
			currentFileHashes: [],
		);
	}

	/**
	 * @param CollectorData $collectedData
	 * @param array<string, array<string>>|null $dependencies
	 */
	private function createAnalyserResult(array $collectedData, ?array $dependencies = []): AnalyserResult
	{
		return new AnalyserResult(
			unorderedErrors: [],
			filteredPhpErrors: [],
			allPhpErrors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			internalErrors: [],
			collectedData: $collectedData,
			dependencies: $dependencies,
			usedTraitDependencies: [],
			packageDependencies: [],
			exportedNodes: [],
			reachedInternalErrorsCountLimit: false,
			peakMemoryUsageBytes: 0,
			processedFiles: [],
		);
	}

}
