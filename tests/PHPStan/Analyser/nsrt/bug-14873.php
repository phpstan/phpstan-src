<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14873;

use function PHPStan\Testing\assertType;
use function in_array;

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

		assertType('true', in_array($full, $a, true));
		assertType('true', in_array($subset, $a, true));
		assertType('bool', in_array($partial, $a, true));
	}

	/**
	 * @param 'a'|'b'|'c' $full
	 * @param 'a'|'b' $subset
	 * @param 'a'|'x' $partial
	 */
	public function literalHaystack(string $full, string $subset, string $partial): void
	{
		assertType('true', in_array($full, ['a', 'b', 'c'], true));
		assertType('bool', in_array($full, ['a', 'b', 'c'], false)); // non-strict
		assertType('true', in_array($subset, ['a', 'b', 'c'], true));
		assertType('bool', in_array($partial, ['a', 'b', 'c'], true));
		assertType('false', in_array($subset, ['x', 'y'], true));

		$fullOrEmpty = rand(0,1) ? $full : [];
		assertType('bool', in_array($fullOrEmpty, ['a', 'b', 'c'], true));
	}

	/**
	 * @param 1|2 $full
	 * @param 1 $subset
	 */
	public function integers(int $full, int $subset): void
	{
		$a = [1, 2];

		assertType('true', in_array($full, $a, true));
		assertType('true', in_array($subset, $a, true));
		assertType('true', in_array($full, [1, 2, 3], true));
	}

	/**
	 * @param Suit::Hearts|Suit::Spades $subset
	 */
	public function enums(Suit $subset): void
	{
		$a = [Suit::Hearts, Suit::Spades, Suit::Clubs];

		assertType('true', in_array($subset, $a, true));
		assertType('true', in_array($subset, [Suit::Hearts, Suit::Spades, Suit::Clubs], true));
		assertType('bool', in_array($subset, [Suit::Hearts], true));
	}

}
