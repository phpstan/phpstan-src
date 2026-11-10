<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15430;

use function PHPStan\Testing\assertType;

/** @template T */
final class Box
{
	/** @param T $value */
	public function __construct(private mixed $value)
	{
	}

	/** @return T */
	public function get(): mixed
	{
		  return $this->value;
	}

	/** @param T $value */
	public function set(mixed $value): void
	{
		  $this->value = $value;
	}

	/**
	 * @template U
	 * @param U $value
	 * @return self<U>
	 */
	public static function of(mixed $value): self
	{
		return new self($value);
	}
}

/** @param callable(): void $callback */
function repeat(int $times, callable $callback): void
{
	for ($i = 0; $i < $times; $i++) {
		$callback();
	}
}

/**
 * @template U
 * @param U $value
 * @return Box<U>
 */
function wrap(mixed $value): Box
{
	return new Box($value);
}

function test(): void
{
	$count = new Box(0);

	repeat(5, function () use ($count): void {
		  $count->set($count->get() + 1);
	});

	assertType('Bug15430\Box<int>', $count);

	if ($count->get() === 5) {
		  echo 'reached at runtime';
	}
}

function straight(): void
{
	$count = new Box(0);
	$count->set($count->get() + 1);
	assertType('Bug15430\Box<int>', $count);
}

function loop(): void
{
	$count = new Box(0);
	for ($i = 0; $i < 5; $i++) {
		$count->set($count->get() + 1);
	}
	assertType('Bug15430\Box<int>', $count);
}

function arrowFunction(): void
{
	$count = new Box(0);
	repeat(5, fn () => $count->set($count->get() + 1));
	assertType('Bug15430\Box<int>', $count);
}

function staticFactory(): void
{
	$count = Box::of(0);
	repeat(5, function () use ($count): void {
		$count->set($count->get() + 1);
	});
	assertType('Bug15430\Box<int>', $count);
}

function genericFunction(): void
{
	$count = wrap(0);
	repeat(5, function () use ($count): void {
		$count->set($count->get() + 1);
	});
	assertType('Bug15430\Box<int>', $count);
}

function concat(): void
{
	$s = new Box('a');
	repeat(5, function () use ($s): void {
		$s->set($s->get() . 'b');
	});
	assertType('Bug15430\Box<string>', $s);
}

function append(): void
{
	$s = new Box([]);
	repeat(5, function () use ($s): void {
		$a = $s->get();
		$a[] = 1;
		$s->set($a);
	});
	assertType('Bug15430\Box<list<int>>', $s);
}

function shape(): void
{
	$s = new Box(['a' => 1]);
	repeat(5, function () use ($s): void {
		$s->set(['a' => $s->get()['a'] + 1]);
	});
	assertType('Bug15430\Box<non-empty-array<string, int>>', $s);
}

function writtenBackUnchanged(): void
{
	$b = new Box(0);
	$b->set($b->get());
	assertType('Bug15430\Box<0>', $b);
}
