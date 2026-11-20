<?php // lint >= 8.0

namespace MissingPureTag;

function add(int $a, int $b): int
{
	return $a + $b;
}

/**
 * Adds one.
 */
function addOne(int $a): int
{
	return $a + 1;
}

/** @phpstan-pure */
function alreadyPure(int $a): int
{
	return $a;
}

/** @phpstan-impure */
function markedImpure(): int
{
	return 1;
}

function echoes(int $a): int
{
	echo $a;
	return $a;
}

function returnsVoid(int $a): void
{
}

function throws(int $a): int
{
	if ($a < 0) {
		throw new \InvalidArgumentException();
	}

	return $a;
}

/** @throws \RuntimeException */
function declaresThrows(int $a): int
{
	return $a;
}

function byRef(int &$a): int
{
	return $a;
}

function callsUnknown(callable $c): mixed
{
	return $c();
}

function generator(): \Generator
{
	yield 1;
}

final class FinalClass
{

	public function __construct(private int $value)
	{
	}

	public function getValue(): int
	{
		return $this->value;
	}

	public function setValue(int $value): int
	{
		$this->value = $value;
		return $value;
	}

}

class OpenClass
{

	public function overridable(): int
	{
		return 1;
	}

	final public function finalMethod(): int
	{
		return 1;
	}

	private function privateMethod(): int
	{
		return 1;
	}

	public static function staticOverridable(): int
	{
		return 1;
	}

}

trait SomeTrait
{

	private function inTrait(): int
	{
		return 1;
	}

}

final class UsesTrait
{

	use SomeTrait;

}

function callsPure(int $a): int
{
	return alreadyPure($a) + 1;
}

function callsImpure(int $a): int
{
	return echoes($a) + 1;
}

function &returnsByRef(array &$items): array
{
	return $items;
}

function readsGlobal(): int
{
	global $counter;
	return $counter;
}

function readsGlobalsArray(): mixed
{
	return $GLOBALS['counter'];
}

function readsServer(): mixed
{
	return $_SERVER['argv'];
}

function staticVariable(): int
{
	static $calls = 0;
	return $calls;
}

final class SharedState
{

	private static int $count = 0;

	/** @var list<int> */
	private array $items = [];

	public function readsStaticProperty(): int
	{
		return self::$count;
	}

	/** @return list<int> */
	public function &itemsByRef(): array
	{
		return $this->items;
	}

}

/** @return int|void */
function maybeReturnsVoid(int $a)
{
	if ($a > 0) {
		return $a;
	}
}

/** @throws \RuntimeException|void */
function maybeThrows(int $a): int
{
	return $a;
}
