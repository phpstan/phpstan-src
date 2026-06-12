<?php declare(strict_types=1); // lint >= 8.3

namespace Bug14501;

function bug14501Trigger(): string
{
	return Bug14501Sub::ATTR_A;
}

/**
 * @template TAttribute of string
 */
abstract class Bug14501Base
{
	/**
	 * @param TAttribute $attribute
	 */
	abstract public function check(string $attribute): bool;
}

/** @extends Bug14501Base<value-of<self::ATTRIBUTES>> */
final class Bug14501Sub extends Bug14501Base
{
	public const string ATTR_A = 'A';
	public const string ATTR_B = 'B';

	public const array ATTRIBUTES = [
		self::ATTR_A,
		self::ATTR_B,
	];

	public function check(string $attribute): bool
	{
		return $attribute === self::ATTR_A;
	}
}
