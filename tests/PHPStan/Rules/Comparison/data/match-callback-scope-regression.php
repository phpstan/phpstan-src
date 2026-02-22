<?php // lint >= 8.1

namespace MatchCallbackScopeRegression;

enum Suit
{
	case Hearts;
	case Diamonds;
}

class Wrapper
{

	public function getSuit(): Suit
	{
		return Suit::Hearts;
	}

}

function exhaustiveMatchWithMethodCallReturningEnum(Wrapper $w): void
{
	match ($w->getSuit()) {
		1 => null,
		default => null,
	};
}
