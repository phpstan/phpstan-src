<?php declare(strict_types = 1);

namespace Bug10666;

class Foo
{
	public function text(int $maxNbChars = 200): string
	{
		$text = [];
		while (empty($text)) {
			$size = 0;
			while ($size < $maxNbChars) {
				$text[] = 'x';
				$size++;
			}
			array_pop($text);
		}
		return implode('', $text);
	}
}

function withArrayShift(int $n): string
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
	return implode('', $text);
}

function withDoWhile(int $n): string
{
	$text = [];
	while (empty($text)) {
		$i = 0;
		do {
			$text[] = 'x';
			$i++;
		} while ($i < $n);
		array_pop($text);
	}
	return implode('', $text);
}

function withForLoop(int $n): string
{
	$text = [];
	while (empty($text)) {
		for ($i = 0; $i < $n; $i++) {
			$text[] = 'x';
		}
		array_pop($text);
	}
	return implode('', $text);
}
