<?php declare(strict_types = 1);

namespace Bug9904;

use function PHPStan\Testing\assertType;

/**
 * @return string[]
 */
function X(): array
{
	$y = Y();
	$data = [];

	if (!is_array($y)) {
		goto ret;
	}

	assertType('array<int, stdClass|null>', $y);

	foreach ($y as $content) {
		$content = json_encode($content);
		if ($content !== false) {
			$data[] = $content;
		}
	}

	ret:
	return $data;
}

/**
 * @return null|array<int, null|\stdClass>
 */
function Y(): ?array
{
	$x = '["a", "b", "c"]';
	$y = json_decode($x, true);
	$z = [];

	if (json_last_error() !== JSON_ERROR_NONE) {
		return null;
	}

	foreach($y as $letter) {
		$num = ord($letter);
		if ($num % 2 === 0) {
			$z[] = null;
		} else {
			$z[] = new \stdClass();
		}
	}

	return $z;
}
