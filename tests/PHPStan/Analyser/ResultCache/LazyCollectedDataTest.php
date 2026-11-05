<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Collectors\CollectedData;
use PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
use PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use function iterator_to_array;

/**
 * @phpstan-import-type CollectorData from CollectedData
 */
class LazyCollectedDataTest extends TestCase
{

	private const METHOD = MethodWithoutImpurePointsCollector::class;
	private const FUNC_CALL = PossiblyPureFuncCallCollector::class;

	public function testFromArrayIsReturnedAsIs(): void
	{
		$data = ['a.php' => [self::METHOD => ['x']]];
		$lazy = LazyCollectedData::fromArray($data);

		$this->assertFalse($lazy->isEmpty());
		$this->assertSame($data, $lazy->toArray());
		$this->assertSame([], $lazy->getCachedIndex());
		$this->assertTrue(LazyCollectedData::fromArray([])->isEmpty());
	}

	public function testCachedEntriesAreReadOnlyWhenAsked(): void
	{
		$reader = $this->createReader([
			'b.php' => [self::METHOD => ['cached b.php']],
			'a.php' => [self::METHOD => ['cached a.php']],
		]);

		$lazy = new LazyCollectedData(['b.php' => [10, 20], 'a.php' => [30, 40]], $reader, []);
		$this->assertFalse($lazy->isEmpty());
		$this->assertSame(0, $reader->reads);

		$this->assertSame([
			'b.php' => [self::METHOD => ['cached b.php']],
			'a.php' => [self::METHOD => ['cached a.php']],
		], $lazy->toArray());
		$this->assertSame(1, $reader->reads);

		$lazy->toArray();
		$this->assertSame(2, $reader->reads, 'nothing is kept between calls');
	}

	public function testFreshEntriesReplaceCachedOnes(): void
	{
		$reader = $this->createReader(['a.php' => [self::METHOD => ['cached']], 'b.php' => [self::METHOD => ['cached']]]);
		$lazy = new LazyCollectedData(['a.php' => [0, 1], 'b.php' => [1, 1]], $reader, ['b.php' => [self::METHOD => ['fresh']], 'c.php' => [self::METHOD => ['fresh']]]);

		$this->assertSame([
			'a.php' => [self::METHOD => ['cached']],
			'b.php' => [self::METHOD => ['fresh']],
			'c.php' => [self::METHOD => ['fresh']],
		], $lazy->toArray());
	}

	public function testIterateYieldsOneCollectorCachedFilesFirst(): void
	{
		$reader = $this->createReader([
			'a.php' => [self::METHOD => ['cached a'], self::FUNC_CALL => ['cached call a']],
			'b.php' => [self::METHOD => ['cached b']],
			'c.php' => [self::FUNC_CALL => ['cached call c']],
		]);
		$lazy = new LazyCollectedData(
			['a.php' => [0, 1], 'b.php' => [1, 1], 'c.php' => [2, 1]],
			$reader,
			['b.php' => [self::METHOD => ['fresh b']], 'd.php' => [self::METHOD => ['fresh d']], 'e.php' => [self::FUNC_CALL => ['fresh call e']]],
		);

		$this->assertSame([
			'a.php' => ['cached a'],
			'b.php' => ['fresh b'],
			'd.php' => ['fresh d'],
		], $this->iterate($lazy, self::METHOD));
		$this->assertSame([self::METHOD => ['a.php']], $reader->readCollectors, 'the cached b.php is replaced by the fresh one, c.php has no data of the collector');

		$this->assertSame([
			'a.php' => ['cached call a'],
			'c.php' => ['cached call c'],
			'e.php' => ['fresh call e'],
		], $this->iterate($lazy, self::FUNC_CALL));
		$this->assertSame(1, $reader->locates, 'the headers are read once');
		$this->assertSame(0, $reader->reads);
	}

	public function testIterateOverFreshDataOnly(): void
	{
		$lazy = LazyCollectedData::fromArray(['a.php' => [self::METHOD => ['a']], 'b.php' => [self::FUNC_CALL => ['b']]]);

		$this->assertSame(['a.php' => ['a']], $this->iterate($lazy, self::METHOD));
		$this->assertSame([], $this->iterate($lazy, 'Unknown\\Collector'));
	}

	public function testHashDoesNotDependOnWhichFilesAreCached(): void
	{
		$data = [
			'a.php' => [self::METHOD => ['a'], self::FUNC_CALL => ['call a']],
			'b.php' => [self::METHOD => ['b']],
			'c.php' => [self::METHOD => ['c']],
		];
		$allFresh = LazyCollectedData::fromArray($data);
		$allCached = new LazyCollectedData(['a.php' => [0, 1], 'b.php' => [1, 1], 'c.php' => [2, 1]], $this->createReader($data), []);
		$mixed = new LazyCollectedData(
			['a.php' => [0, 1], 'c.php' => [2, 1]],
			$this->createReader(['a.php' => $data['a.php'], 'c.php' => $data['c.php']]),
			['b.php' => $data['b.php']],
		);

		$hash = $allFresh->hashCollector(self::METHOD);
		$this->assertSame($hash, $allCached->hashCollector(self::METHOD));
		$this->assertSame($hash, $mixed->hashCollector(self::METHOD));
		$this->assertSame($allFresh->hashCollector(self::FUNC_CALL), $mixed->hashCollector(self::FUNC_CALL));
		$this->assertNotSame($hash, $allFresh->hashCollector(self::FUNC_CALL));

		$changed = $data;
		$changed['b.php'][self::METHOD] = ['changed b'];
		$this->assertNotSame($hash, LazyCollectedData::fromArray($changed)->hashCollector(self::METHOD));
	}

	public function testWithRenamedFile(): void
	{
		$reader = $this->createReader(['cached.php' => [self::METHOD => ['cached']]]);
		$lazy = (new LazyCollectedData(['cached.php' => [0, 1]], $reader, ['tmp.php' => [self::METHOD => ['fresh']]]))
			->withRenamedFile('tmp.php', 'renamed.php');

		$this->assertSame(['cached.php' => [0, 1]], $lazy->getCachedIndex());
		$this->assertSame([
			'cached.php' => [self::METHOD => ['cached']],
			'renamed.php' => [self::METHOD => ['fresh']],
		], $lazy->toArray());
	}

	public function testWithRenamedFileRefusesCachedFile(): void
	{
		$lazy = new LazyCollectedData(['cached.php' => [0, 1]], $this->createReader([]), []);

		$this->expectException(ShouldNotHappenException::class);
		$lazy->withRenamedFile('cached.php', 'renamed.php');
	}

	public function testCachedIndexNeedsReader(): void
	{
		$this->expectException(ShouldNotHappenException::class);
		new LazyCollectedData(['cached.php' => [0, 1]], null, []);
	}

	/**
	 * @return array<string, list<mixed>>
	 */
	private function iterate(LazyCollectedData $lazy, string $collectorType): array
	{
		return iterator_to_array($lazy->iterate($collectorType));
	}

	/**
	 * @param CollectorData $data what the cache file holds
	 */
	private function createReader(array $data): FakeCollectedDataReader
	{
		return new FakeCollectedDataReader($data);
	}

}
