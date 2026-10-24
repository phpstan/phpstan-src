<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15406;

use function PHPStan\Testing\assertType;

final class Node
{
	/** @param list<self> $children */
	public function __construct(public readonly array $children, public readonly int $value) {}
}

/**
 * @template T
 * @template U
 *
 * @param list<T> $list
 * @param callable(T): iterable<U> $fn
 *
 * @return list<U>
 */
function collect(array $list, callable $fn): array
{
	$result = [];
	foreach ($list as $value) {
		foreach ($fn($value) as $item) {
			$result[] = $item;
		}
	}

	return $result;
}

/**
 * @param list<Node> $nodes
 */
function twoLevels(array $nodes): void
{
	assertType('list<int>', collect($nodes, static fn (Node $node): array
		=> collect($node->children, static fn (Node $child): array => [$child->value]),
	));
}

/**
 * @param list<Node> $nodes
 */
function threeLevels(array $nodes): void
{
	assertType('list<int>', collect($nodes, static fn (Node $node): array
		=> collect($node->children, static fn (Node $child): array
			=> collect($child->children, static fn (Node $grandChild): array => [$grandChild->value]),
		),
	));
}

/**
 * @param list<Node> $nodes
 */
function threeLevelsGenerators(array $nodes): void
{
	assertType('list<int>', collect($nodes, static fn (Node $node): iterable
		=> yield from collect($node->children, static fn (Node $child): iterable
			=> yield from collect($child->children, static fn (Node $grandChild): iterable => yield $grandChild->value),
		),
	));
}

/**
 * @param list<Node> $nodes
 */
function threeLevelsClosures(array $nodes): void
{
	assertType('list<int>', collect($nodes, static function (Node $node): array {
		return collect($node->children, static function (Node $child): array {
			return collect($child->children, static function (Node $grandChild): array {
				return [$grandChild->value];
			});
		});
	}));
}

/**
 * @param list<Node> $nodes
 */
function threeLevelsClosuresWithoutReturnTypes(array $nodes): void
{
	assertType('list<int>', collect($nodes, static function (Node $node) {
		return collect($node->children, static function (Node $child) {
			return collect($child->children, static function (Node $grandChild) {
				return [$grandChild->value];
			});
		});
	}));
}

/**
 * @param list<Node> $nodes
 */
function fiveLevels(array $nodes): void
{
	assertType('list<int>', collect($nodes, static fn (Node $a): array
		=> collect($a->children, static fn (Node $b): array
			=> collect($b->children, static fn (Node $c): array
				=> collect($c->children, static fn (Node $d): array
					=> collect($d->children, static fn (Node $e): array => [$e->value]),
				),
			),
		),
	));
}

/**
 * @template T
 */
final class Box
{
	/** @param T $value */
	public function __construct(public mixed $value)
	{
	}

	/**
	 * @template U
	 * @param callable(T): U $callback
	 * @return Box<U>
	 */
	public function map(callable $callback): Box
	{
		return new Box($callback($this->value));
	}

	/**
	 * @template V
	 * @template U
	 * @param V $value
	 * @param callable(V): U $callback
	 * @return Box<U>
	 */
	public static function mapStatic(mixed $value, callable $callback): Box
	{
		return new Box($callback($value));
	}
}

/**
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function run(callable $callback): mixed
{
	return $callback();
}

function boxThreeLevels(): void
{
	$box = new Box(1);
	assertType('Bug15406\Box<Bug15406\Box<Bug15406\Box<array{k: 1}>>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): Box => (new Box($j))->map(fn (int $k): array => ['k' => $k]))));
}

function boxStaticThreeLevels(): void
{
	assertType('Bug15406\Box<Bug15406\Box<Bug15406\Box<array{k: 1}>>>', Box::mapStatic(1, fn (int $i): Box => Box::mapStatic($i, fn (int $j): Box => Box::mapStatic($j, fn (int $k): array => ['k' => $k]))));
}

function boxTwoLevels(): void
{
	/** @var Box<int> $box */
	$box = new Box(1);
	assertType('Bug15406\Box<Bug15406\Box<array{j: int}>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): array => ['j' => $j])));
}

run(function (): void {
	$box = new Box(1);
	assertType('Bug15406\Box<Bug15406\Box<array{j: 1}>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): array => ['j' => $j])));

	/** @var Box<int> $box */
	$box = new Box(1);
	assertType('Bug15406\Box<Bug15406\Box<array{j: int}>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): array => ['j' => $j])));
});

function arrayMapFourLevels(): void
{
	assertType('array{array{array{array{array{1}}}}}', array_map(fn ($x) => array_map(fn ($y) => array_map(fn ($z) => array_map(fn ($w) => [$w], [$z]), [$y]), [$x]), [1]));
}
