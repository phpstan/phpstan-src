<?php declare(strict_types = 1);

namespace Bug7508;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $data
 */
function loopy (array $data ): void {

	foreach ($data as $key =>$value) {
		if(!is_array($value)) {
			continue;
		}
		assertType('array<mixed, mixed>', $data[$key]);
		$data[$key][0] = 'test';

	}
}

/**
 * @param array<mixed> $data
 */
function loopy2 (array $data ): void {

	foreach ($data as $key =>$value) {
		if(!is_int($value)) {
			continue;
		}
		// Expected int, got mixed
		assertType('int', $data[$key]);

	}
}

/**
 * @param array<mixed> $data
 */
function loopyValueReassigned (array $data ): void {

	foreach ($data as $key => $value) {
		if(!is_int($value)) {
			continue;
		}
		// the element itself did not change - the narrowing persists
		$value = 'foo';
		assertType('int', $data[$key]);
	}
}

/**
 * @param array<mixed> $data
 */
function loopyKeyReassigned (array $data ): void {

	foreach ($data as $key => $value) {
		if(!is_int($value)) {
			continue;
		}
		$key = 'foo';
		assertType('mixed', $data[$key]);
	}
}

/**
 * @param array<mixed> $data
 */
function loopyIterateeReassigned (array $data ): void {

	foreach ($data as $key => $value) {
		$data[$key] = 1;
		if(!is_string($value)) {
			continue;
		}
		assertType('1', $data[$key]);
	}
}
