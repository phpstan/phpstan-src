<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPUnit\Framework\TestCase;

class BenchmarkShardsTest extends TestCase
{

	public function testSplit(): void
	{
		// Handing these out in turn would put 10, 7, 5 and 2 (24) into one shard and 8, 6 and 4 (18)
		// into the other. Always picking the cheaper shard ends up with 22 and 20.
		$costs = ['c.php' => 7, 'a.php' => 10, 'e.php' => 5, 'b.php' => 8, 'd.php' => 6, 'f.php' => 4, 'g.php' => 2];

		$this->assertSame([
			['a.php', 'd.php', 'f.php', 'g.php'],
			['b.php', 'c.php', 'e.php'],
		], BenchmarkShards::split($costs, 2));
	}

	public function testSplitBreaksTiesByName(): void
	{
		$this->assertSame([
			['a.php', 'd.php'],
			['b.php'],
			['c.php'],
		], BenchmarkShards::split(['d.php' => 1, 'c.php' => 1, 'b.php' => 1, 'a.php' => 1], 3));
	}

	public function testSplitIntoMoreShardsThanBenchmarks(): void
	{
		$this->assertSame([['a.php'], ['b.php']], BenchmarkShards::split(['a.php' => 3, 'b.php' => 1], 4));
	}

}
