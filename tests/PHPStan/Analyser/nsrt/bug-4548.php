<?php

namespace Bug4548Nsrt;

use function PHPStan\Testing\assertType;

abstract class Enum
{

	/** @var static::* */
	private int $value;

	/** @param static::* $value */
	public function __construct(int $value)
	{
		// static is any subclass of Enum - only the native type is certain
		assertType('int', $value);
		$this->value = $value;
	}

	/** @return static::* */
	public function getValue(): int
	{
		assertType('int', $this->value);

		return $this->value;
	}

}

final class Suit extends Enum
{

	public const HEARTS = 1;
	public const DIAMONDS = 2;
	public const SPADES = 3;
	public const CLUBS = 4;

}

function (Suit $suit): void {
	assertType('1|2|3|4', $suit->getValue());
};
