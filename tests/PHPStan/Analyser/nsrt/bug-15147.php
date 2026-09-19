<?php declare(strict_types = 1);

namespace Bug15147;

use ArrayIterator;
use ArrayObject;
use SplObjectStorage;
use stdClass;
use function PHPStan\Testing\assertType;
use function rand;

class Test
{

	/**
	 * @param ArrayObject<array-key, array<mixed>|mixed> $alias
	 */
	public function populate(ArrayObject $alias): void
	{
		if (rand(1, 10) > 5) {
			// Append an example array
			$alias->append(['item', 13]);
		} else {
			$alias->append('void');
		}
	}

	public function test(): void
	{
		$alias = new ArrayObject();
		assertType('ArrayObject<*NEVER*, *NEVER*>', $alias);
		$this->populate($alias);
		assertType('ArrayObject<(int|string), mixed>', $alias);
		$alias = $alias->getArrayCopy();
		assertType('array<mixed>', $alias);

		foreach ($alias as $k => $v) {
			if (!is_array($v)) {
				$alias[$k] = [];
			}
		}
	}

}

/** @template T */
class Coll
{

	/** @var array<T> */
	private array $items;

	/** @param array<T> $items */
	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	/** @param T $item */
	public function add($item): void
	{
	}

	/** @return array<T> */
	public function all(): array
	{
		return $this->items;
	}

}

class Consumer
{

	/** @param Coll<mixed> $c */
	public function __construct(Coll $c)
	{
	}

	/** @param Coll<mixed> $c */
	public static function consumeStatically(Coll $c): void
	{
	}

}

class ImpureConsumer
{

	/**
	 * @param Coll<mixed> $c
	 * @phpstan-impure
	 */
	public function __construct(Coll $c)
	{
	}

}

/** @param Coll<mixed> $c */
function consume(Coll $c): void
{
}

/**
 * @param Coll<mixed> $c
 * @phpstan-pure
 */
function consumePurely(Coll $c): int
{
	return 1;
}

/** @param array<Coll<mixed>> $colls */
function consumeMany(array $colls): void
{
}

class Holder
{

	/** @var Coll<mixed> */
	public static Coll $staticColl;

	/** @var Coll<mixed> */
	public Coll $coll;

}

function methodWithSideEffectsCalledOnIt(): void
{
	$c = new Coll();
	assertType('Bug15147\Coll<*NEVER*>', $c);
	$c->add('foo');
	assertType('Bug15147\Coll<mixed>', $c);
	assertType('array<mixed>', $c->all());
}

function passedToFunction(): void
{
	$c = new Coll();
	consume($c);
	assertType('Bug15147\Coll<mixed>', $c);
}

function passedToStaticMethod(): void
{
	$c = new Coll();
	Consumer::consumeStatically($c);
	assertType('Bug15147\Coll<mixed>', $c);
}

function passedToConstructor(): void
{
	// an unmarked constructor is assumed not to mutate its arguments,
	// see nsrt/impure-constructor.php
	$c = new Coll();
	new Consumer($c);
	assertType('Bug15147\Coll<*NEVER*>', $c);

	$c2 = new Coll();
	new ImpureConsumer($c2);
	assertType('Bug15147\Coll<mixed>', $c2);
}

function passedInsideArray(): void
{
	$colls = [new Coll()];
	consumeMany($colls);
	assertType('array{Bug15147\Coll<mixed>}', $colls);
}

function passedToPureFunction(): void
{
	$c = new Coll();
	consumePurely($c);
	assertType('Bug15147\Coll<*NEVER*>', $c);
}

function inProperty(Holder $h): void
{
	$h->coll = new Coll();
	assertType('Bug15147\Coll<*NEVER*>', $h->coll);
	consume($h->coll);
	assertType('Bug15147\Coll<mixed>', $h->coll);
}

function inStaticProperty(): void
{
	Holder::$staticColl = new Coll();
	assertType('Bug15147\Coll<*NEVER*>', Holder::$staticColl);
	consume(Holder::$staticColl);
	assertType('Bug15147\Coll<mixed>', Holder::$staticColl);
}

function inUnion(): void
{
	$c = rand(0, 1) === 0 ? new Coll() : null;
	assertType('Bug15147\Coll<*NEVER*>|null', $c);
	if ($c !== null) {
		consume($c);
		assertType('Bug15147\Coll<mixed>', $c);
	}
}

function writtenThroughArrayAccess(): void
{
	$a = new ArrayObject();
	$a['x'] = 1;
	assertType('ArrayObject<(int|string), mixed>', $a);

	$b = new ArrayObject();
	$b[] = 1;
	assertType('ArrayObject<(int|string), mixed>', $b);
}

function otherSplClasses(): void
{
	$s = new SplObjectStorage();
	$s->attach(new stdClass());
	assertType('SplObjectStorage<object, mixed>', $s);

	$i = new ArrayIterator();
	$i->append(1);
	assertType('ArrayIterator<(int|string), mixed>', $i);
}
