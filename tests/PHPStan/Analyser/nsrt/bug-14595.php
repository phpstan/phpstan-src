<?php

namespace Bug14595;

use function PHPStan\Testing\assertType;

/**
 * @param array{
 *     multiple: 0|1|2
 *   , total: bool
 *  } $options
 */
function arrayAppendGuard(array $options): void {
	$instructions = [ ];
	$instructions[] = "foo";
	if ($options['multiple'] != 1 || $options['total'])
		$instructions[] = "bar";
	assertType('0|1|2', $options['multiple']);
	if (!$options['total'])
		$instructions[] = "baz";
	assertType('0|1|2', $options['multiple']);
	if (!$options['total'])
		$instructions[] = "qux";
	assertType('0|1|2', $options['multiple']);
}

/**
 * @param array{
 *     multiple: 0|1|2
 *   , total: bool
 *  } $options
 */
function strictComparisonGuard(array $options): void {
	$instructions = [ ];
	$instructions[] = "foo";
	if ($options['multiple'] !== 1 || $options['total'])
		$instructions[] = "bar";
	assertType('0|1|2', $options['multiple']);
	if (!$options['total'])
		$instructions[] = "baz";
	assertType('0|1|2', $options['multiple']);
	if (!$options['total'])
		$instructions[] = "qux";
	assertType('0|1|2', $options['multiple']);
}
