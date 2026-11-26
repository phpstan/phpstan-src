<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Override;
use PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
use PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector;
use PHPUnit\Framework\TestCase;
use function gc_collect_cycles;
use function is_file;
use function iterator_to_array;
use function ksort;
use function md5;
use function mkdir;
use function rmdir;
use function serialize;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class CollectedDataSpoolTest extends TestCase
{

	private const METHOD = MethodWithoutImpurePointsCollector::class;
	private const FUNC_CALL = PossiblyPureFuncCallCollector::class;

	private string $directory;

	#[Override]
	protected function setUp(): void
	{
		$this->directory = sys_get_temp_dir() . '/phpstan-spool-' . uniqid();
		mkdir($this->directory);
	}

	#[Override]
	protected function tearDown(): void
	{
		@unlink($this->path());
		@rmdir($this->directory);
	}

	public function testNothingAppendedCreatesNoFile(): void
	{
		$spool = new CollectedDataSpool($this->path());
		$spool->append([]);

		$this->assertNull($spool->finish());
		$this->assertSame([], $spool->toArray());
		$this->assertFalse(is_file($this->path()));
	}

	public function testSpooledDataIsTheSameAsInMemory(): void
	{
		$batches = [
			[
				'a.php' => [self::METHOD => ['a1', 'a2'], self::FUNC_CALL => [['call', 1]]],
				'trait.php' => [self::METHOD => ['from a']],
			],
			[
				'b.php' => [self::FUNC_CALL => [['call', 2]]],
				// a trait is stored under its own file, and each file using it can be in a different job
				'trait.php' => [self::METHOD => ['from b'], self::FUNC_CALL => [['call', 3]]],
			],
		];
		$inMemory = [];
		$spool = new CollectedDataSpool($this->path());
		foreach ($batches as $batch) {
			$spool->append($batch);
			foreach ($batch as $file => $dataPerCollector) {
				foreach ($dataPerCollector as $collectorType => $values) {
					foreach ($values as $value) {
						$inMemory[$file][$collectorType][] = $value;
					}
				}
			}
		}

		$this->assertSame($this->sorted($inMemory), $this->sorted($spool->toArray()));

		$spooled = $spool->finish();
		$this->assertNotNull($spooled);
		$this->assertSame(['a.php' => true, 'trait.php' => true, 'b.php' => true], $spooled->getFiles());
		$this->assertSame($inMemory['trait.php'], $spooled->read('trait.php'));

		$lazy = new LazyCollectedData([], null, [], $spooled);
		$fromArray = LazyCollectedData::fromArray($inMemory);
		foreach ([self::METHOD, self::FUNC_CALL] as $collectorType) {
			$this->assertSame(iterator_to_array($fromArray->iterate($collectorType)), iterator_to_array($lazy->iterate($collectorType)));
			$this->assertSame($fromArray->hashCollector($collectorType), $lazy->hashCollector($collectorType));
		}
		$this->assertSame(md5(serialize(['from a', 'from b'])), $spooled->getHashes(self::METHOD)['trait.php']);
		$this->assertSame($inMemory, $lazy->toArray());
	}

	public function testSpooledFilesReplaceCachedOnes(): void
	{
		$spool = new CollectedDataSpool($this->path());
		$spool->append(['b.php' => [self::METHOD => ['spooled b']], 'c.php' => [self::METHOD => ['spooled c']]]);
		$spooled = $spool->finish();

		$reader = new FakeCollectedDataReader(['a.php' => [self::METHOD => ['cached a']], 'b.php' => [self::METHOD => ['cached b']]]);
		$lazy = new LazyCollectedData(['a.php' => [0, 1], 'b.php' => [1, 1]], $reader, [], $spooled);

		$this->assertSame([
			'a.php' => ['cached a'],
			'b.php' => ['spooled b'],
			'c.php' => ['spooled c'],
		], iterator_to_array($lazy->iterate(self::METHOD)));
		$this->assertSame([
			'a.php' => [self::METHOD => ['cached a']],
			'b.php' => [self::METHOD => ['spooled b']],
			'c.php' => [self::METHOD => ['spooled c']],
		], $lazy->toArray());
		$this->assertSame(
			LazyCollectedData::fromArray(['a.php' => [self::METHOD => ['cached a']], 'b.php' => [self::METHOD => ['spooled b']], 'c.php' => [self::METHOD => ['spooled c']]])->hashCollector(self::METHOD),
			$lazy->hashCollector(self::METHOD),
		);
	}

	public function testOnlyAndRename(): void
	{
		$spool = new CollectedDataSpool($this->path());
		$spool->append(['a.php' => [self::METHOD => ['a']], 'tmp.php' => [self::METHOD => ['tmp'], self::FUNC_CALL => ['call']]]);
		$spooled = $spool->finish();
		$this->assertNotNull($spooled);

		$this->assertSame(['a.php' => ['a']], iterator_to_array($spooled->only(['a.php' => true])->iterate(self::METHOD)));
		$this->assertTrue($spooled->only(['other.php' => true])->isEmpty());

		$renamed = (new LazyCollectedData([], null, [], $spooled))->withRenamedFile('tmp.php', 'real.php');
		$this->assertSame(['a.php' => ['a'], 'real.php' => ['tmp']], iterator_to_array($renamed->iterate(self::METHOD)));
		$this->assertSame(['real.php' => ['call']], iterator_to_array($renamed->iterate(self::FUNC_CALL)));
	}

	public function testFileIsDeletedWithTheSpool(): void
	{
		$spool = new CollectedDataSpool($this->path());
		$spool->append(['a.php' => [self::METHOD => ['a']]]);
		$spooled = $spool->finish();
		$this->assertTrue(is_file($this->path()));

		unset($spool);
		$this->assertTrue(is_file($this->path()), 'the spooled data still reads the file');

		unset($spooled);
		gc_collect_cycles();
		$this->assertFalse(is_file($this->path()));
	}

	private function path(): string
	{
		return $this->directory . '/collectedData.spool';
	}

	/**
	 * @param array<string, array<string, list<mixed>>> $data
	 * @return array<string, array<string, list<mixed>>>
	 */
	private function sorted(array $data): array
	{
		ksort($data);
		foreach ($data as $file => $dataPerCollector) {
			ksort($dataPerCollector);
			$data[$file] = $dataPerCollector;
		}

		return $data;
	}

}
