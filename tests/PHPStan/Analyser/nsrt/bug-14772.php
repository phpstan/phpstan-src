<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14772;

use function PHPStan\Testing\assertType;

function testMatch(mixed $x): void
{
	$class = match ($x) {
		'aa' => 'some_class',
		'bb', 'cc' => 'another_class',
		default => null,
	};
	if ($class === null) {
		return;
	}
	assertType("'aa'|'bb'|'cc'", $x);
}

function testMatchNarrowToSingle(mixed $x): void
{
	$class = match ($x) {
		'aa' => 'some_class',
		'bb', 'cc' => 'another_class',
		default => null,
	};
	if ($class === 'some_class') {
		assertType("'aa'", $x);
	}
	if ($class === 'another_class') {
		assertType("'bb'|'cc'", $x);
	}
}

function testMatchTrueSubject(mixed $x): void
{
	$class = match (true) {
		$x === 'aa' => 'some_class',
		$x === 'bb', $x === 'cc' => 'another_class',
		default => null,
	};
	if ($class === null) {
		return;
	}
	assertType("'aa'|'bb'|'cc'", $x);
}

function testMatchIntSubject(int $x): void
{
	$label = match ($x) {
		1 => 'one',
		2, 3 => 'few',
		default => 'many',
	};
	if ($label === 'one') {
		assertType('1', $x);
	}
	if ($label === 'few') {
		assertType('2|3', $x);
	}
}

function testMatchNonNullDefault(mixed $x): void
{
	$class = match ($x) {
		'aa' => 1,
		'bb' => 2,
		default => 3,
	};
	if ($class === 3) {
		assertType("mixed~('aa'|'bb')", $x);
	}
	if ($class === 1) {
		assertType("'aa'", $x);
	}
}

function testMatchSameBodyType(int $y): void
{
	$x = match ($y) {
		1 => 'a',
		2 => 'a',
		default => 'b',
	};
	if ($x === 'a') {
		assertType('1|2', $y);
	}
}

enum Suit: string
{

	case Hearts = 'H';
	case Spades = 'S';
	case Clubs = 'C';

}

function testMatchEnumSubject(Suit $s): void
{
	$color = match ($s) {
		Suit::Hearts => 'red',
		Suit::Spades, Suit::Clubs => 'black',
	};
	if ($color === 'red') {
		assertType('Bug14772\Suit::Hearts', $s);
	}
	if ($color === 'black') {
		assertType('Bug14772\Suit::Clubs|Bug14772\Suit::Spades', $s);
	}
}
