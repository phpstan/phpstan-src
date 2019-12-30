<?php

function (
	$mixed,
	array $array,
	int $integer,
	?string $nullableString
) {
	$mixed ?? 10;
	$array ?? 10;
	$integer ?? 10;
	(function (): bool {
		return true;
	})() ?? false;
	$nullableString ?? true;
	(string) $mixed ?? 10;
};

/**
 * @param int|string $union
 * @param int|stdClass|null $nullableUnion
 */
function foo(
	$union,
	$nullableUnion
) {
	$union ?? 10;
	$nullableUnion ?? 10;
}

function(
	string $one,
	string $two,
	?string $nullableString
) {
	strpos($one, $two) ?? 'foo';
	if (!is_string($nullableString)) {
		$nullableString ?? 10;
	}
};
