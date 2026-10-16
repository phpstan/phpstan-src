<?php declare(strict_types = 1);

namespace Bug15434;

class Test
{
    public function testSet(): void
    {
        $map = new WeakAnalysingMap();
        $map->set($map);
	}
}

/**
 * @template TOwner of object
 */
class WeakAnalysingMap
{
    /**
     * @param TOwner $owner
     */
    public function set(object $owner): void {}
}