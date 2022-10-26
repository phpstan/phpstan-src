<?php

namespace Bug7220;

function filter(callable $predicate, iterable $iterable): \Iterator {
	foreach ($iterable as $key => $value) {
		if ($predicate($value)) {
			yield $key => $value;
		}
	}
}

function getFiltered(): \Iterator {
	$already_seen = [];
	return filter(function (string $value) use (&$already_seen): bool {
		$result = !isset($already_seen[$value]);
		$already_seen[$value] = TRUE;
		return $result;
	}, ['a', 'b', 'a']);
}
