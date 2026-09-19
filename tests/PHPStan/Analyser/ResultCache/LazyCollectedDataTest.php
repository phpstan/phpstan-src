<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use function array_keys;

class LazyCollectedDataTest extends TestCase
{

	private int $reads = 0;

	public function testFromArrayIsReturnedAsIs(): void
	{
		$data = ['a.php' => [MethodWithoutImpurePointsCollector::class => ['x']]];
		$lazy = LazyCollectedData::fromArray($data);

		$this->assertFalse($lazy->isEmpty());
		$this->assertSame($data, $lazy->toArray());
		$this->assertSame([], $lazy->getCachedIndex());
		$this->assertTrue(LazyCollectedData::fromArray([])->isEmpty());
	}

	public function testCachedEntriesAreReadOnlyWhenAsked(): void
	{
		$reader = function (array $index): array {
			$this->reads++;
			$result = [];
			foreach (array_keys($index) as $file) {
				$result[$file] = [MethodWithoutImpurePointsCollector::class => ['cached ' . $file]];
			}

			return $result;
		};

		$lazy = new LazyCollectedData(['b.php' => [10, 20], 'a.php' => [30, 40]], $reader, []);
		$this->assertFalse($lazy->isEmpty());
		$this->assertSame(0, $this->reads);

		$this->assertSame([
			'b.php' => [MethodWithoutImpurePointsCollector::class => ['cached b.php']],
			'a.php' => [MethodWithoutImpurePointsCollector::class => ['cached a.php']],
		], $lazy->toArray());
		$this->assertSame(1, $this->reads);

		$lazy->toArray();
		$this->assertSame(2, $this->reads, 'nothing is kept between calls');
	}

	public function testFreshEntriesReplaceCachedOnes(): void
	{
		$reader = static fn (array $index): array => ['a.php' => [MethodWithoutImpurePointsCollector::class => ['cached']], 'b.php' => [MethodWithoutImpurePointsCollector::class => ['cached']]];
		$lazy = new LazyCollectedData(['a.php' => [0, 1], 'b.php' => [1, 1]], $reader, ['b.php' => [MethodWithoutImpurePointsCollector::class => ['fresh']], 'c.php' => [MethodWithoutImpurePointsCollector::class => ['fresh']]]);

		$this->assertSame([
			'a.php' => [MethodWithoutImpurePointsCollector::class => ['cached']],
			'b.php' => [MethodWithoutImpurePointsCollector::class => ['fresh']],
			'c.php' => [MethodWithoutImpurePointsCollector::class => ['fresh']],
		], $lazy->toArray());
	}

	public function testWithRenamedFile(): void
	{
		$reader = static fn (array $index): array => ['cached.php' => [MethodWithoutImpurePointsCollector::class => ['cached']]];
		$lazy = (new LazyCollectedData(['cached.php' => [0, 1]], $reader, ['tmp.php' => [MethodWithoutImpurePointsCollector::class => ['fresh']]]))
			->withRenamedFile('tmp.php', 'renamed.php');

		$this->assertSame(['cached.php' => [0, 1]], $lazy->getCachedIndex());
		$this->assertSame([
			'cached.php' => [MethodWithoutImpurePointsCollector::class => ['cached']],
			'renamed.php' => [MethodWithoutImpurePointsCollector::class => ['fresh']],
		], $lazy->toArray());
	}

	public function testWithRenamedFileRefusesCachedFile(): void
	{
		$lazy = new LazyCollectedData(['cached.php' => [0, 1]], static fn (array $index): array => [], []);

		$this->expectException(ShouldNotHappenException::class);
		$lazy->withRenamedFile('cached.php', 'renamed.php');
	}

}
