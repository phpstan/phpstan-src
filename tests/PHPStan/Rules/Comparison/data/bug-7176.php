<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug7176\Comparison;

enum Suit
{
	case Hearts;
	case Diamonds;
	case Clubs;
	case Spades;
}

function test(Suit $x): string {
	if ($x === Suit::Clubs) {
		return 'WORKS';
	}
	// Suit::Clubs is correctly eliminated from possible values

	if (in_array($x, [Suit::Spades], true)) {
		return 'DOES NOT WORK';
	}
	// Suit::Spades is not eliminated from possible values

	return match ($x) { // no error is expected here
		Suit::Hearts => 'a',
		Suit::Diamonds => 'b',
	};
}
