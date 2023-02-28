<?php declare(strict_types=1);

namespace GetObjectVars81;

use function PHPStan\Testing\assertType;

enum Suit
{
	case Hearts;
	case Diamonds;
	case Clubs;
	case Spades;
}

enum SuitBacked: string
{
	case Hearts = 'H';
	case Diamonds = 'D';
	case Clubs = 'C';
	case Spades = 'S';
}

enum NumBacked: int
{
	case One = 1;
	case Two = 2;
	case Three = 3;
}

function check(Suit $suit, SuitBacked $backed, NumBacked $num): void
{
	assertType("array{name: 'Clubs'|'Diamonds'|'Hearts'|'Spades'}", get_object_vars($suit));
	assertType("array{name: 'Clubs'|'Diamonds'|'Hearts'|'Spades', value: 'C'|'D'|'H'|'S'}", get_object_vars($backed));
	assertType("array{name: 'One'|'Three'|'Two', value: 1|2|3}", get_object_vars($num));
}


