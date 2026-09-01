<?php // lint >= 8.0

namespace Bug6732;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(array $items = [])
	{
	}

	/** @param T $item */
	public function add($item): void
	{
	}

	/** @return T */
	public function get()
	{
	}

}

/** @template T */
class Bag
{

	public function __construct()
	{
	}

	/** @param T $item */
	public function add($item): void
	{
	}

	/** @return T */
	public function get()
	{
	}

}

/** @template-covariant T */
class Box
{

	/** @param T $value */
	public function __construct($value)
	{
	}

}

/** @template-covariant T */
class EmptyBox
{

	public function __construct()
	{
	}

}

/** @param Collection<int> $ints */
function takeInts(Collection $ints): void
{
}

/** @param Collection<string> $strings */
function takeStrings(Collection $strings): void
{
}

/** @param Collection<covariant int> $c */
function takeCovariantInts(Collection $c): void
{
}

/** @param Collection<contravariant int> $c */
function takeContravariantInts(Collection $c): void
{
}

/** @param Collection<*> $c */
function takeAny(Collection $c): void
{
}

/** @param Bag<int> $b */
function takeBagOfInts(Bag $b): void
{
}

/** @param Box<int> $b */
function takeBoxOfInts(Box $b): void
{
}

/** @param EmptyBox<int> $b */
function takeEmptyBoxOfInts(EmptyBox $b): void
{
}

/**
 * @template T
 * @param T $item
 * @return Collection<T>
 */
function make($item): Collection
{
}

/**
 * @template T
 * @param Collection<T> $c
 * @return Bag<T>
 */
function wrap(Collection $c): Bag
{
}

class Sends
{

	/** @var Collection<int> */
	private Collection $ints;

	/** @var Collection<string> */
	private Collection $strings;

	/** @var Bag<int> */
	private Bag $bagOfInts;

	public function propertySend(): void
	{
		$c = new Collection([1]);
		assertType('Bug6732\Collection<int>', $c);
		$this->ints = $c;
		assertType('Bug6732\Collection<int>', $c);
	}

	public function firstSendWins(): void
	{
		$array = new Collection([]);
		$this->ints = $array;
		$this->strings = $array;
		assertType('Bug6732\Collection<int>', $array);
	}

	/** @return Collection<int> */
	public function returnSend(): Collection
	{
		$c = new Collection([1]);
		assertType('Bug6732\Collection<int>', $c);

		return $c;
	}

	public function sendWinsOverLowerBound(): void
	{
		$b = new Bag();
		$b->add(1);
		$this->bagOfInts = $b;
		$b->add('a');
		assertType('Bug6732\Bag<int>', $b);
	}

}

function (): void {
	$c = new Collection([1]);
	takeInts($c);
	assertType('Bug6732\Collection<int>', $c);
};

function (): void {
	$c = new Collection([1]);
	takeStrings($c);
	assertType('Bug6732\Collection<1>', $c);
};

function (): void {
	$ints = new Collection([1]);
	/** @var Collection<int> $x */
	$x = $ints;
	assertType('Bug6732\Collection<int>', $ints);
};

function (): void {
	$c = new Collection([1]);
	$f = function () use ($c): void {
		takeInts($c);
	};
	assertType('Bug6732\Collection<int>', $c);
};

function (): void {
	$c = new Collection([1]);
	$f = fn () => takeInts($c);
	assertType('Bug6732\Collection<int>', $c);
};

function (bool $foo): void {
	$c = new Collection([1]);
	takeInts($foo ? $c : new Collection([2]));
	assertType('Bug6732\Collection<int>', $c);
};

function (): void {
	$c = new Collection([1]);
	assertType('Bug6732\Collection<1>', $c);
	assertType('1', $c->get());
};

function (): void {
	$b = new Bag();
	assertType('Bug6732\Bag<*NEVER*>', $b);
	$c = new Collection();
	assertType('Bug6732\Collection<*NEVER*>', $c);
	$e = new Collection([]);
	assertType('Bug6732\Collection<*NEVER*>', $e);
};

function (): void {
	$b = new Bag();
	$b->add(1);
	$b->add('a');
	assertType("Bug6732\Bag<1|'a'>", $b);
	assertType("1|'a'", $b->get());
};

function (): void {
	$b = new Bag();
	takeBagOfInts($b);
	assertType('Bug6732\Bag<int>', $b);
};

function (): void {
	$x = new Box(1);
	takeBoxOfInts($x);
	assertType('Bug6732\Box<1>', $x);

	$e = new EmptyBox();
	takeEmptyBoxOfInts($e);
	assertType('Bug6732\EmptyBox<int>', $e);
};

function (): void {
	$a = new Collection([1]);
	takeCovariantInts($a);
	assertType('Bug6732\Collection<1>', $a);

	$b = new Collection([1]);
	takeContravariantInts($b);
	assertType('Bug6732\Collection<int>', $b);

	$c = new Collection([1]);
	takeAny($c);
	assertType('Bug6732\Collection<1>', $c);
};

function (): void {
	$c = make(1);
	takeInts($c);
	assertType('Bug6732\Collection<int>', $c);
	assertType('Bug6732\Collection<int>', make(2));
};

function (): void {
	$c = new Collection([1]);
	$b = wrap($c);
	takeBagOfInts($b);
	assertType('Bug6732\Collection<int>', $c);
	assertType('Bug6732\Bag<int>', $b);
};
