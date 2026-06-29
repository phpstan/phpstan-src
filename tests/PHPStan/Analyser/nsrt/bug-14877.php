<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14877;

use function PHPStan\Testing\assertType;
use function array_search;

enum Suit: string
{

	case Hearts = 'H';
	case Spades = 'S';
	case Clubs = 'C';

}

class HelloWorld
{

	/**
	 * @param 'a'|'b'|'c' $full
	 * @param 'a'|'b' $subset
	 * @param 'a'|'x' $partial
	 */
	public function variableHaystack(string $full, string $subset, string $partial): void
	{
		$a = ['a', 'b', 'c'];

		assertType('0|1|2', array_search($full, $a, true));
		assertType('0|1', array_search($subset, $a, true));
		assertType('0|false', array_search($partial, $a, true));
	}

	/**
	 * @param 'a'|'b'|'c' $full
	 * @param 'a'|'b' $subset
	 * @param 'a'|'x' $partial
	 */
	public function literalHaystack(string $full, string $subset, string $partial): void
	{
		assertType('0|1|2', array_search($full, ['a', 'b', 'c'], true));
		assertType('0|1|2|false', array_search($full, ['a', 'b', 'c'], false)); // non-strict
		assertType('0|1', array_search($subset, ['a', 'b', 'c'], true));
		assertType('0|false', array_search($partial, ['a', 'b', 'c'], true));
		assertType('false', array_search($subset, ['x', 'y'], true));
	}

	/**
	 * @param 1|2 $full
	 * @param 1 $subset
	 */
	public function integers(int $full, int $subset): void
	{
		$a = [1, 2];

		assertType('0|1', array_search($full, $a, true));
		assertType('0', array_search($subset, $a, true));
		assertType('0|1', array_search($full, [1, 2, 3], true));
	}

	/**
	 * @param Suit::Hearts|Suit::Spades $subset
	 */
	public function enums(Suit $subset): void
	{
		$a = [Suit::Hearts, Suit::Spades, Suit::Clubs];

		assertType('0|1', array_search($subset, $a, true));
		assertType('0|1', array_search($subset, [Suit::Hearts, Suit::Spades, Suit::Clubs], true));
		assertType('0|false', array_search($subset, [Suit::Hearts], true));
	}

	/**
	 * Plain objects do not have a finite set of possible values, so array_search()
	 * must not drop false even when the needle's class matches every haystack value.
	 */
	public function objects(Article $article, ?Article $a, ?Article $b): void
	{
		$haystack = [$a, $b];

		assertType('0|1|false', array_search($article, $haystack, true));
		assertType('0|1|false', array_search($article, [$a, $b], true));
	}

	/**
	 * A general (non-constant) array only guarantees a value's presence when it is
	 * non-empty and all its values share a single finite type.
	 *
	 * @param 1|2 $needle
	 * @param array<int, 1|2> $maybeEmpty
	 * @param non-empty-array<int, 1|2> $nonEmptyMulti
	 * @param non-empty-array<int, 1> $nonEmptySingle
	 */
	public function generalArrays(int $needle, array $maybeEmpty, array $nonEmptyMulti, array $nonEmptySingle): void
	{
		assertType('int|false', array_search($needle, $maybeEmpty, true));
		assertType('int|false', array_search($needle, $nonEmptyMulti, true));
		assertType('int', array_search(1, $nonEmptySingle, true));
	}

	/**
	 * A known offset value (HasOffsetValueType) guarantees a strict search finds
	 * the needle, even when the needle is an enum case rather than a scalar.
	 *
	 * @param array<string, Suit> $arr
	 * @param array<string, 1|2> $ints
	 */
	public function knownOffsetValue(array $arr, array $ints): void
	{
		if (($arr['x'] ?? null) === Suit::Hearts) {
			assertType('string', array_search(Suit::Hearts, $arr, true));
		}

		if (($ints['x'] ?? null) === 1) {
			assertType('string', array_search(1, $ints, true));
			assertType('string|false', array_search(2, $ints, true));
		}
	}

}

class Article
{

}
