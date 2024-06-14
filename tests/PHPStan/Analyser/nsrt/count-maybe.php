<?php

namespace CountMaybe;

use Countable;
use function PHPStan\Testing\assertType;

function doBar1(float $notCountable, int $mode): void
{
	if (count($notCountable, $mode) > 0) {
		assertType('float', $notCountable);
	} else {
		assertType('float', $notCountable);
	}
	assertType('float', $notCountable);
}

/**
 * @param array|int $maybeMode
 */
function doBar2(float $notCountable, $maybeMode): void
{
	if (count($notCountable, $maybeMode) > 0) {
		assertType('float', $notCountable);
	} else {
		assertType('float', $notCountable);
	}
	assertType('float', $notCountable);
}

function doBar3(float $notCountable, float $invalidMode): void
{
	if (count($notCountable, $invalidMode) > 0) {
		assertType('float', $notCountable);
	} else {
		assertType('float', $notCountable);
	}
	assertType('float', $notCountable);
}

/**
 * @param float|int[] $maybeCountable
 */
function doFoo1($maybeCountable, int $mode): void
{
	if (count($maybeCountable, $mode) > 0) {
		assertType('non-empty-array<int>', $maybeCountable);
	} else {
		assertType('array<int>|float', $maybeCountable);
	}
	assertType('array<int>|float', $maybeCountable);
}

/**
 * @param float|int[] $maybeCountable
 * @param array|int $maybeMode
 */
function doFoo2($maybeCountable, $maybeMode): void
{
	if (count($maybeCountable, $maybeMode) > 0) {
		assertType('non-empty-array<int>', $maybeCountable);
	} else {
		assertType('array<int>|float', $maybeCountable);
	}
	assertType('array<int>|float', $maybeCountable);
}

/**
 * @param float|int[] $maybeCountable
 */
function doFoo3($maybeCountable, float $invalidMode): void
{
	if (count($maybeCountable, $invalidMode) > 0) {
		assertType('non-empty-array<int>', $maybeCountable);
	} else {
		assertType('array<int>|float', $maybeCountable);
	}
	assertType('array<int>|float', $maybeCountable);
}

/**
 * @param float|list<int> $maybeCountable
 */
function doFoo4($maybeCountable, int $mode): void
{
	if (count($maybeCountable, $mode) > 0) {
		assertType('non-empty-list<int>', $maybeCountable);
	} else {
		assertType('list<int>|float', $maybeCountable);
	}
	assertType('list<int>|float', $maybeCountable);
}

/**
 * @param float|list<int> $maybeCountable
 * @param array|int $maybeMode
 */
function doFoo5($maybeCountable, $maybeMode): void
{
	if (count($maybeCountable, $maybeMode) > 0) {
		assertType('non-empty-list<int>', $maybeCountable);
	} else {
		assertType('list<int>|float', $maybeCountable);
	}
	assertType('list<int>|float', $maybeCountable);
}

/**
 * @param float|list<int> $maybeCountable
 */
function doFoo6($maybeCountable, float $invalidMode): void
{
	if (count($maybeCountable, $invalidMode) > 0) {
		assertType('non-empty-list<int>', $maybeCountable);
	} else {
		assertType('list<int>|float', $maybeCountable);
	}
	assertType('list<int>|float', $maybeCountable);
}

/**
 * @param float|list<int>|Countable $maybeCountable
 */
function doFoo7($maybeCountable, int $mode): void
{
	if (count($maybeCountable, $mode) > 0) {
		assertType('non-empty-list<int>|Countable', $maybeCountable);
	} else {
		assertType('list<int>|Countable|float', $maybeCountable);
	}
	assertType('list<int>|Countable|float', $maybeCountable);
}

/**
 * @param float|list<int>|Countable $maybeCountable
 * @param array|int $maybeMode
 */
function doFoo8($maybeCountable, $maybeMode): void
{
	if (count($maybeCountable, $maybeMode) > 0) {
		assertType('non-empty-list<int>|Countable', $maybeCountable);
	} else {
		assertType('list<int>|Countable|float', $maybeCountable);
	}
	assertType('list<int>|Countable|float', $maybeCountable);
}

/**
 * @param float|list<int>|Countable $maybeCountable
 */
function doFoo9($maybeCountable, float $invalidMode): void
{
	if (count($maybeCountable, $invalidMode) > 0) {
		assertType('non-empty-list<int>|Countable', $maybeCountable);
	} else {
		assertType('list<int>|Countable|float', $maybeCountable);
	}
	assertType('list<int>|Countable|float', $maybeCountable);
}

function doFooBar1(array $countable, int $mode): void
{
	if (count($countable, $mode) > 0) {
		assertType('non-empty-array', $countable);
	} else {
		assertType('array{}', $countable);
	}
	assertType('array', $countable);
}

/**
 * @param array|int $maybeMode
 */
function doFooBar2(array $countable, $maybeMode): void
{
	if (count($countable, $maybeMode) > 0) {
		assertType('non-empty-array', $countable);
	} else {
		assertType('array{}', $countable);
	}
	assertType('array', $countable);
}

function doFooBar3(array $countable, float $invalidMode): void
{
	if (count($countable, $invalidMode) > 0) {
		assertType('non-empty-array', $countable);
	} else {
		assertType('array{}', $countable);
	}
	assertType('array', $countable);
}
