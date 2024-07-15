<?php declare(strict_types = 1);

namespace BinaryOpBenevolentUnion;

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function plus($benevolent, Foo $object): void
{
	echo $benevolent + 1;
	echo $benevolent + [];
	echo $benevolent + $object;
	echo $benevolent + '123';
	echo $benevolent + 1.23;
	echo $benevolent + $benevolent;

	$a = 1;
	$a += $benevolent;

	$a = [];
	$a += $benevolent;

	$a = $object;
	$a += $benevolent;

	$a = '123';
	$a += $benevolent;

	$a = 1.23;
	$a += $benevolent;

	$a = $benevolent;
	$a += $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function exponent($benevolent, Foo $object): void
{
	echo $benevolent ** 1;
	echo $benevolent ** [];
	echo $benevolent ** $object;
	echo $benevolent ** '123';
	echo $benevolent ** 1.23;
	echo $benevolent ** $benevolent;

	$a = 1;
	$a **= $benevolent;

	$a = [];
	$a **= $benevolent;

	$a = $object;
	$a **= $benevolent;

	$a = '123';
	$a **= $benevolent;

	$a = 1.23;
	$a **= $benevolent;

	$a = $benevolent;
	$a **= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function mul($benevolent, Foo $object): void
{
	echo $benevolent * 1;
	echo $benevolent * [];
	echo $benevolent * $object;
	echo $benevolent * '123';
	echo $benevolent * 1.23;
	echo $benevolent * $benevolent;

	$a = 1;
	$a *= $benevolent;

	$a = [];
	$a *= $benevolent;

	$a = $object;
	$a *= $benevolent;

	$a = '123';
	$a *= $benevolent;

	$a = 1.23;
	$a *= $benevolent;

	$a = $benevolent;
	$a *= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function div($benevolent, Foo $object): void
{
	echo $benevolent / 1;
	echo $benevolent / [];
	echo $benevolent / $object;
	echo $benevolent / '123';
	echo $benevolent / 1.23;
	echo $benevolent / $benevolent;

	$a = 1;
	$a /= $benevolent;

	$a = [];
	$a /= $benevolent;

	$a = $object;
	$a /= $benevolent;

	$a = '123';
	$a /= $benevolent;

	$a = 1.23;
	$a /= $benevolent;

	$a = $benevolent;
	$a /= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function mod($benevolent, Foo $object): void
{
	echo $benevolent % 1;
	echo $benevolent % [];
	echo $benevolent % $object;
	echo $benevolent % '123';
	echo $benevolent % 1.23;
	echo $benevolent % $benevolent;

	$a = 1;
	$a %= $benevolent;

	$a = [];
	$a %= $benevolent;

	$a = $object;
	$a %= $benevolent;

	$a = '123';
	$a %= $benevolent;

	$a = 1.23;
	$a %= $benevolent;

	$a = $benevolent;
	$a %= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function minus($benevolent, Foo $object): void
{
	echo $benevolent - 1;
	echo $benevolent - [];
	echo $benevolent - $object;
	echo $benevolent - '123';
	echo $benevolent - 1.23;
	echo $benevolent - $benevolent;

	$a = 1;
	$a -= $benevolent;

	$a = [];
	$a -= $benevolent;

	$a = $object;
	$a -= $benevolent;

	$a = '123';
	$a -= $benevolent;

	$a = 1.23;
	$a -= $benevolent;

	$a = $benevolent;
	$a -= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function concat($benevolent, Foo $object): void
{
	echo $benevolent . 1;
	echo $benevolent . [];
	echo $benevolent . $object;
	echo $benevolent . '123';
	echo $benevolent . 1.23;
	echo $benevolent . $benevolent;

	$a = 1;
	$a .= $benevolent;

	$a = [];
	$a .= $benevolent;

	$a = $object;
	$a .= $benevolent;

	$a = '123';
	$a .= $benevolent;

	$a = 1.23;
	$a .= $benevolent;

	$a = $benevolent;
	$a .= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function lshift($benevolent, Foo $object): void
{
	echo $benevolent << 1;
	echo $benevolent << [];
	echo $benevolent << $object;
	echo $benevolent << '123';
	echo $benevolent << 1.23;
	echo $benevolent << $benevolent;

	$a = 1;
	$a <<= $benevolent;

	$a = [];
	$a <<= $benevolent;

	$a = $object;
	$a <<= $benevolent;

	$a = '123';
	$a <<= $benevolent;

	$a = 1<<23;
	$a <<= $benevolent;

	$a = $benevolent;
	$a <<= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function rshift($benevolent, Foo $object): void
{
	echo $benevolent >> 1;
	echo $benevolent >> [];
	echo $benevolent >> $object;
	echo $benevolent >> '123';
	echo $benevolent >> 1.23;
	echo $benevolent >> $benevolent;

	$a = 1;
	$a >>= $benevolent;

	$a = [];
	$a >>= $benevolent;

	$a = $object;
	$a >>= $benevolent;

	$a = '123';
	$a >>= $benevolent;

	$a = 1>>23;
	$a >>= $benevolent;

	$a = $benevolent;
	$a >>= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function bitAnd($benevolent, Foo $object): void
{
	echo $benevolent & 1;
	echo $benevolent & [];
	echo $benevolent & $object;
	echo $benevolent & '123';
	echo $benevolent & 1.23;
	echo $benevolent & $benevolent;

	$a = 1;
	$a &= $benevolent;

	$a = [];
	$a &= $benevolent;

	$a = $object;
	$a &= $benevolent;

	$a = '123';
	$a &= $benevolent;

	$a = 1.23;
	$a &= $benevolent;

	$a = $benevolent;
	$a &= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function bitXor($benevolent, Foo $object): void
{
	echo $benevolent ^ 1;
	echo $benevolent ^ [];
	echo $benevolent ^ $object;
	echo $benevolent ^ '123';
	echo $benevolent ^ 1.23;
	echo $benevolent ^ $benevolent;

	$a = 1;
	$a ^= $benevolent;

	$a = [];
	$a ^= $benevolent;

	$a = $object;
	$a ^= $benevolent;

	$a = '123';
	$a ^= $benevolent;

	$a = 1.23;
	$a ^= $benevolent;

	$a = $benevolent;
	$a ^= $benevolent;
}

/**
 * @param __benevolent<array<string, string>|int|object|bool|resource> $benevolent
 */
function bitOr($benevolent, Foo $object): void
{
	echo $benevolent | 1;
	echo $benevolent | [];
	echo $benevolent | $object;
	echo $benevolent | '123';
	echo $benevolent | 1.23;
	echo $benevolent | $benevolent;

	$a = 1;
	$a |= $benevolent;

	$a = [];
	$a |= $benevolent;

	$a = $object;
	$a |= $benevolent;

	$a = '123';
	$a |= $benevolent;

	$a = 1.23;
	$a |= $benevolent;

	$a = $benevolent;
	$a |= $benevolent;
}

class Foo {}
