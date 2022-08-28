<?php

namespace ListType;

use function PHPStan\Testing\assertType;

class Suit {
	public const DIAMOND = 'diamond';
	public const SPADE = 'spade';
	public const HEART = 'heart';
	public const CLUB = 'club';
}

/**
 * @param Exclude<'foo'|'bar'|'baz', 'baz'> $str1
 * @param Exclude<'foo'|'bar'|'baz', 'abc'> $str2
 * @param Exclude<'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q'|'r'|'s'|'t'|'u'|'v'|'w'|'x'|'y'|'z', 'a'|'h'|'z'> $str3
 * @param Exclude<1|2|3, 1> $int1
 * @param Exclude<1|2|3, 4> $int2
 * @param Exclude<1|2|3|4|5|6|7|8|9|10|11, 1|4|6|10> $int3
 */
function foo1(string $str1, string $str2, string $str3, int $int1, int $int2, int $int3): void
{
	assertType("'bar'|'foo'", $str1);
	assertType("'bar'|'baz'|'foo'", $str2);
	assertType("'b'|'c'|'d'|'e'|'f'|'g'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q'|'r'|'s'|'t'|'u'|'v'|'w'|'x'|'y'", $str3);
	assertType('2|3', $int1);
	assertType('1|2|3', $int2);
	assertType('2|3|5|7|8|9|11', $int3);
}

/**
 * @param Suit::* $suit
 */
function suit1(string $suit): void {
	assertType("'club'|'diamond'|'heart'|'spade'", $suit);
}

/**
 * @param Suit::DIAMOND|Suit::HEART|Suit::CLUB $suit
 */
function suit2(string $suit): void {
	assertType("'club'|'diamond'|'heart'", $suit);
}

/**
 * @param Exclude<Suit::*, Suit::HEART> $suit
 */
function suit3(string $suit): void {
	assertType("'club'|'diamond'|'spade'", $suit);
}

/**
 * @param Exclude<Suit::*, Suit::HEART|Suit::SPADE> $suit
 */
function suit4(string $suit): void {
	assertType("'club'|'diamond'", $suit);
}
