<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug10418;

function (): void {
	$text = '123';
	$result = match(1){
		preg_match('/(\d+)/', $text, $match) => 'matched number: ' . $match[1],
		preg_match('/(\w+)/', $text, $match) => 'matched word: ' . json_encode($match),
		default => 'no matches!'
	};
};
