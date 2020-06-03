<?php

namespace Analyser\Bug2851;

use function PHPStan\Analyser\assertType;

function (array $input) {
	$results = [];
	foreach ($input as $value) {
		$results[] = $value;

		assertType('array<int, mixed>', $results);
		assertType('int', count($results));
	}
};

function (array $input) {
	$results = [];
	foreach ($input as $value) {
		array_unshift($results, $value);

		assertType('array<int, mixed>&nonEmpty', $results);
		assertType('int', count($results));
	}
};

function (array $input, string $key) {
	$results = [$key => []];
	foreach ($input as $value) {
		$results[$key][] = $value;

		assertType('array<int, mixed>', $results[$key]);
		assertType('int', count($results[$key]));
	}
};

function (array $input, string $key) {
	$results = [$key => []];
	foreach ($input as $value) {
		array_push($results[$key], $value);

		assertType('mixed', $results[$key]);
		assertType('int', count($results[$key]));
	}
};

function (array $input, string $key) {
	$results = [$key => []];
	foreach ($input as $value) {
		array_unshift($results[$key], $value);

		assertType('mixed', $results[$key]);
		assertType('int', count($results[$key]));
	}
};
