<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPUnit\Framework\TestCase;

class LRUCacheTest extends TestCase
{

	public function testBasics(): void
	{
		$cache = new LRUCache(3);

		$cache->put('1', '1');
		$cache->put('2', '2');
		$cache->put('3', '3');
		self::assertSame([1, 2, 3], $cache->getKeys());

		$cache->get('2');
		self::assertSame([1, 3, 2], $cache->getKeys());

		$cache->put('4', '4');
		self::assertSame([3, 2, 4], $cache->getKeys());

		$cache->get('2');
		self::assertSame([3, 4, 2], $cache->getKeys());

		$cache->get('2');
		self::assertSame([3, 4, 2], $cache->getKeys());

		$cache->put('5', '5');
		self::assertSame([4, 2, 5], $cache->getKeys());
	}

}
