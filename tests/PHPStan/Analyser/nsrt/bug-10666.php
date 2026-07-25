<?php declare(strict_types = 1);

namespace Bug10666;

use function PHPStan\Testing\assertType;

function popInNestedLoop(int $n): string
{
	$text = [];
	while (empty($text)) {
		$i = 0;
		while ($i < $n) {
			$text[] = 'x';
			$i++;
		}
		assertType("list<'x'>", $text);
		array_pop($text);
		assertType("list<'x'>", $text);
	}
	assertType("non-empty-list<'x'>", $text);
	return implode('', $text);
}

function shiftInNestedLoop(int $n): string
{
	$text = [];
	while (empty($text)) {
		$i = 0;
		while ($i < $n) {
			$text[] = 'x';
			$i++;
		}
		array_shift($text);
	}
	assertType("non-empty-list<'x'>", $text);
	return implode('', $text);
}
