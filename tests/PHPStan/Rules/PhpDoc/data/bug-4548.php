<?php

namespace Bug4548PhpDoc;

abstract class Enum
{
	/** @var static::* */
	private int $value;

	/** @param static::* $value */
	public function __construct(int $value)
	{
		$this->value = $value;
	}
}

final class Suit extends Enum
{
	public const HEARTS = 1;
	public const DIAMONDS = 2;
	public const SPADES = 3;
	public const CLUBS = 4;
}

new Suit(Suit::HEARTS);
new Suit(5);
