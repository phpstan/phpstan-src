<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug7176;

use function PHPStan\Testing\assertType;

enum Suit
{
	case Hearts;
	case Diamonds;
	case Clubs;
	case Spades;
}

function test(Suit $x): string {
	if ($x === Suit::Clubs) {
		assertType('Bug7176\Suit::Clubs', $x);
		return 'WORKS';
	}
	assertType('Bug7176\Suit::Diamonds|Bug7176\Suit::Hearts|Bug7176\Suit::Spades', $x);

	if (in_array($x, [Suit::Spades], true)) {
		assertType('Bug7176\Suit::Spades', $x);
		return 'DOES NOT WORK';
	}
	assertType('Bug7176\Suit::Diamonds|Bug7176\Suit::Hearts', $x);

	return match ($x) {
		Suit::Hearts => 'a',
		Suit::Diamonds => 'b',
	};
}
