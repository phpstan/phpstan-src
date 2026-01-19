<?php

namespace Bug13964;

/** @var array<string, array<mixed>> $state */
$state = (fn()=>[])();

$state = array_map(function (array $item): array {
	if (array_key_exists('type', $item) && array_key_exists('data', $item)) {
		return $item;
	}

	return [
		'type' => 'hello',
		'data' => [],
	];
}, $state);
