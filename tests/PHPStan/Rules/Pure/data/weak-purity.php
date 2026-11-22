<?php declare(strict_types = 1);

namespace WeakPurity;

final class Holder
{

	/** @var list<int> */
	public array $items = [];

	/** @var list<int> */
	public static array $shared = [];

}

/**
 * @phpstan-pure
 * @param list<int> $items
 * @return list<int>
 */
function sortsLocalCopy(array $items): array
{
	sort($items);
	array_push($items, 1);
	settype($items, 'array');
	preg_match('/a/', 'abc', $matches);
	preg_match_all('/a/', 'abc', $allMatches);

	return $items;
}

/**
 * @phpstan-pure
 * @param list<string> $items
 * @return list<string>
 */
function sortsWithPureCallback(array $items): array
{
	usort($items, static fn (string $a, string $b): int => $a <=> $b);

	return $items;
}

/**
 * @phpstan-pure
 * @param list<string> $items
 * @return list<string>
 */
function sortsWithImpureCallback(array $items): array
{
	usort($items, static function (string $a, string $b): int {
		echo $a;
		return $a <=> $b;
	});

	return $items;
}

/**
 * @phpstan-pure
 */
function sortsProperty(Holder $holder): int
{
	sort($holder->items);

	return 1;
}

/**
 * @phpstan-pure
 */
function sortsStaticProperty(): int
{
	sort(Holder::$shared);

	return 1;
}

/**
 * @phpstan-pure
 */
function sortsSuperglobal(): int
{
	sort($_GET);

	return 1;
}

/**
 * @phpstan-pure
 * @param list<int> $items
 */
function shufflesLocalCopy(array $items): int
{
	shuffle($items);

	return $items[0] ?? 0;
}
