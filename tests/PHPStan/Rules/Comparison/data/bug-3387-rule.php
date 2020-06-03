<?php declare(strict_types = 1);

function (array $items, string $key) {
	$results = [$key => []];
	foreach ($items as $item) {
		array_unshift($results[$key], $item);
		if (count($results[$key]) > 1) {
			throw new RuntimeException();
		}
	}
};

function (array $items, string $key) {
	$results = [$key => []];
	foreach ($items as $item) {
		array_push($results[$key], $item);
		if (count($results[$key]) > 1) {
			throw new RuntimeException();
		}
	}
};
