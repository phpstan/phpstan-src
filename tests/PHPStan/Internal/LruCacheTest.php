<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use function array_keys;

class LruCacheTest extends PHPStanTestCase
{

	public function testMissingEntry(): void
	{
		$cache = new LruCache();

		$this->assertNull($cache->get('a'));
		$this->assertSame(0, $cache->count());
	}

	public function testGetTouchesTheEntry(): void
	{
		$cache = new LruCache();
		$cache->set('a', 'A', 1);
		$cache->set('b', 'B', 1);

		$this->assertSame(['a', 'b'], array_keys($cache->all()));

		$this->assertSame('A', $cache->get('a'));
		$this->assertSame(['b', 'a'], array_keys($cache->all()));
	}

	public function testCountEvictionDropsTheLeastRecentlyUsed(): void
	{
		$cache = new LruCache(maxCount: 2);
		$this->assertSame([], $cache->set('a', 'A', 1));
		$this->assertSame([], $cache->set('b', 'B', 1));

		// 'a' is touched, so 'b' becomes the oldest and goes first
		$cache->get('a');
		$this->assertSame(['b'], $cache->set('c', 'C', 1));
		$this->assertSame(['a', 'c'], array_keys($cache->all()));
		$this->assertSame(2, $cache->count());
	}

	public function testWeightEvictionMakesRoomForTheIncomingEntry(): void
	{
		$cache = new LruCache(maxWeight: 10);
		$cache->set('a', 'A', 6);
		$this->assertSame([], $cache->set('b', 'B', 4));

		$this->assertSame(['a', 'b'], $cache->set('c', 'C', 10));
		$this->assertSame(['c'], array_keys($cache->all()));
	}

	public function testWeightEvictionStopsAtTheFloor(): void
	{
		$cache = new LruCache(maxWeight: 10, weightEvictionFloorCount: 2);
		$cache->set('a', 'A', 5);
		$cache->set('b', 'B', 5);

		// the floor keeps two entries even though the incoming one does not fit
		$this->assertSame([], $cache->set('c', 'C', 100));
		$this->assertSame(['a', 'b', 'c'], array_keys($cache->all()));
	}

	public function testSetReplacesAnEntryAndAccountsForItsNewWeight(): void
	{
		$cache = new LruCache(maxWeight: 10);
		$cache->set('a', 'A', 9);
		$cache->set('a', 'AA', 2);

		// the 9 bytes of the replaced entry are free again, so 8 more fit
		$this->assertSame([], $cache->set('b', 'B', 8));
		$this->assertSame(['a' => 'AA', 'b' => 'B'], $cache->all());
	}

	public function testReplaceKeepsTheWeightAndEvictsNothing(): void
	{
		$cache = new LruCache(maxCount: 2, maxWeight: 10);
		$cache->set('a', 'A', 5);
		$cache->set('b', 'B', 5);

		$cache->replace('a', 'AAA');

		$this->assertSame(['b' => 'B', 'a' => 'AAA'], $cache->all());
		$this->assertSame(2, $cache->count());
	}

	public function testReplacingAMissingEntryIsABug(): void
	{
		$cache = new LruCache();

		$this->expectException(ShouldNotHappenException::class);
		$cache->replace('a', 'A');
	}

	public function testNoLimitsMeansNoEviction(): void
	{
		$cache = new LruCache();
		for ($i = 0; $i < 100; $i++) {
			$this->assertSame([], $cache->set('k' . $i, $i, 1_000_000));
		}

		$this->assertSame(100, $cache->count());
	}

}
