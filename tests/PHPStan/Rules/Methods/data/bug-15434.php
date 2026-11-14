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

/** @template T */
class Box
{

	/** @param T $v */
	public function __construct($v = null)
	{
	}

	/** @param T $v */
	public function add($v): void
	{
	}

	/** @param list<T> $v */
	public function addAll(array $v): void
	{
	}

}

class Analogous
{

	public function indirectCycle(): void
	{
		$a = new WeakAnalysingMap();
		$b = new WeakAnalysingMap();
		$a->set($b);
		$b->set($a);
	}

	public function afterAnotherLowerBound(): void
	{
		$b = new Box();
		$b->add(1);
		$b->add($b);
	}

	public function wrappedInList(): void
	{
		$b = new Box();
		$b->addAll([$b]);
	}

	public function wrappedInArray(): void
	{
		$b = new Box();
		$b->add([$b]);
	}

	public function withInitialType(): void
	{
		$b = new Box(1);
		$b->add($b);
	}

}
