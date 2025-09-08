<?php

namespace Bug3387;

function (array $items, string $key) {
	$array = [$key => []];
	foreach ($items as $item) {
		$array[$key][] = $item;
		if (count($array[$key]) > 1) {
			throw new RuntimeException();
		}
	}
};

function (array $items, string $key) {
	$array = [$key => []];
	foreach ($items as $item) {
		array_unshift($array[$key], $item);
		if (count($array[$key]) > 1) {
			throw new RuntimeException();
		}
	}
};

function (array $items, string $key) {
	$array = [$key => []];
	foreach ($items as $item) {
		array_push($array[$key], $item);
		if (count($array[$key]) > 1) {
			throw new RuntimeException();
		}
	}
};
