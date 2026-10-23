<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11421;

enum NumberEnum: int
{
	case One = 1;
	case Two = 2;
}

enum WordEnum: string
{
	case One = 'one';
	case Two = 'two';
}

$word = 'one';
NumberEnum::from($word);

$number = 1;
WordEnum::from($number);

function (string $s, int $i, \BackedEnum $e): void {
	NumberEnum::tryFrom($s);
	NumberEnum::from($i);
	NumberEnum::tryFrom($i);
	WordEnum::tryFrom($i);
	WordEnum::from($s);
	WordEnum::tryFrom($s);
	$e::from($s);
	$e::tryFrom($i);
	$from = NumberEnum::from(...);
	$from($s);
};
