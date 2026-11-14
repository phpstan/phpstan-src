<?php declare(strict_types = 1);

namespace Bug15434Types;

use function PHPStan\Testing\assertType;

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
	public function add($v): void
	{
	}

}

/** @template-contravariant T */
class Sink
{

	/** @param T $v */
	public function put($v): void
	{
	}

}

class Test
{

	public function testSet(): void
	{
		$map = new WeakAnalysingMap();
		assertType('Bug15434Types\WeakAnalysingMap<object>', $map);
		$map->set($map);
		assertType('Bug15434Types\WeakAnalysingMap<object>', $map);
	}

	public function indirectCycle(): void
	{
		$a = new WeakAnalysingMap();
		$b = new WeakAnalysingMap();
		$a->set($b);
		$b->set($a);
		assertType('Bug15434Types\WeakAnalysingMap<Bug15434Types\WeakAnalysingMap<object>>', $a);
		assertType('Bug15434Types\WeakAnalysingMap<object>', $b);
	}

	public function afterAnotherLowerBound(): void
	{
		$b = new Box();
		$b->add(1);
		$b->add($b);
		assertType('Bug15434Types\Box<mixed>', $b);
	}

	public function contravariant(): void
	{
		$s = new Sink();
		$s->put($s);
		assertType('Bug15434Types\Sink<mixed>', $s);
	}

	public function notACycle(): void
	{
		$b = new Box();
		$c = new Box();
		$b->add($c);
		$c->add(1);
		assertType('Bug15434Types\Box<1>', $c);
		assertType('Bug15434Types\Box<Bug15434Types\Box<1>>', $b);
	}

}
