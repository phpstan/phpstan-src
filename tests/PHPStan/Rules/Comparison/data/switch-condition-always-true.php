<?php // lint >= 8.1

namespace SwitchConditionAlwaysTrue;

enum Suit
{

	case Hearts;
	case Diamonds;

}

class Foo
{

	public function redundantCaseAfterExhaustiveEnum(Suit $suit): void
	{
		switch ($suit) {
			case Suit::Hearts:
				break;
			case Suit::Diamonds:
				break;
			case Suit::Hearts:
				break;
		}
	}

	public function lastCaseAlwaysTrueIsAllowed(Suit $suit): void
	{
		switch ($suit) {
			case Suit::Hearts:
				break;
			case Suit::Diamonds:
				break;
		}
	}

	/**
	 * @param 1|2 $i
	 */
	public function intUnion(int $i): void
	{
		switch ($i) {
			case 1:
				break;
			case 2:
				break;
			case 3:
				break;
		}
	}

	public function alwaysTrueBeforeDefault(Suit $suit): void
	{
		switch ($suit) {
			case Suit::Hearts:
				break;
			case Suit::Diamonds:
				break;
			default:
				break;
		}
	}

}
