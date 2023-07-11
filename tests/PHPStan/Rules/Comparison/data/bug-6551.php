<?php

namespace Bug6551;

use function PHPStan\Testing\assertType;

function (): void {
	$data = [
		'c1' => 12,
		'rasd' => 13,
		'c34' => 15,
	];

	foreach ($data as $key => $value) {
		$match = [];
		if (false === preg_match('/^c(\d+)$/', $key, $match) || empty($match)) {
			continue;
		}
		var_dump($key);
		var_dump($value);
	}
};

function (): void {
	$data = [
		'c1' => 12,
		'rasd' => 13,
		'c34' => 15,
	];

	foreach ($data as $key => $value) {
		if (false === preg_match('/^c(\d+)$/', $key, $match) || empty($match)) {
			continue;
		}
		var_dump($key);
		var_dump($value);
	}
};

function (): void {
	$data = [
		'c1' => 12,
		'rasd' => 13,
		'c34' => 15,
	];

	foreach ($data as $key => $value) {
		$match = [];
		assertType('bool', preg_match('/^c(\d+)$/', $key, $match) || empty($match));
	}
};

function (): void {
	$data = [
		'c1' => 12,
		'rasd' => 13,
		'c34' => 15,
	];

	foreach ($data as $key => $value) {
		assertType('bool', preg_match('/^c(\d+)$/', $key, $match) || empty($match));
	}
};
