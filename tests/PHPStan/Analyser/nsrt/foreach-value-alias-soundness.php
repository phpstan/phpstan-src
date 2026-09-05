<?php declare(strict_types = 1);

namespace ForeachValueAliasSoundness;

use function PHPStan\Testing\assertType;
use function extract;

/**
 * The foreach value variable aliases $data[$key] only while nothing wrote the
 * value/key/iteratee since the narrowing. These cover write paths that bypass
 * the assignment-time containment invalidation, plus a cross-iteration write.
 */

/**
 * @param array<int|string> $data
 */
function byRefClosureUse(array $data): void
{
	foreach ($data as $key => $value) {
		$fn = function () use (&$value): void {
			$value = 5;
		};
		$fn();
		if (is_int($value)) {
			// the closure desynced $value from $data[$key]: the element is unchanged
			assertType('int|string', $data[$key]);
		}
	}
}

/**
 * @param array<int|string> $data
 */
function dynamicWrite(array $data): void
{
	foreach ($data as $key => $value) {
		$name = 'value';
		$$name = 5;
		if (is_int($value)) {
			assertType('int|string', $data[$key]);
		}
	}
}

/**
 * A dynamic-variable dim write ($$name is $data) into the iteratee at a foreign
 * key desyncs the live element from the snapshot value variable, exactly like a
 * statically named $data[$key + 1] write - the dynamic base must not slip past
 * the alias detector.
 *
 * @param array<int|string> $data
 */
function dynamicDimWrite(array $data): void
{
	foreach ($data as $key => $value) {
		$name = 'data';
		$$name[$key + 1] = 'str';
		if (is_int($value)) {
			assertType('int|string', $data[$key]);
		}
	}
}

/**
 * A dynamic write to the key variable ($$name is $key) means $data[$key] no
 * longer refers to the snapshot value's element.
 *
 * @param array<int|string> $data
 */
function dynamicKeyWrite(array $data): void
{
	foreach ($data as $key => $value) {
		$name = 'key';
		$$name = 99;
		if (is_int($value)) {
			assertType('int|string', $data[$key]);
		}
	}
}

/**
 * A curly-brace dynamic variable (${'data'}) is a Variable with an Expr name in
 * the AST, so a dim write through it must be treated as a possible iteratee write.
 *
 * @param array<int|string> $data
 */
function curlyDynamicDimWrite(array $data): void
{
	foreach ($data as $key => $value) {
		${'data'}[$key + 1] = 'str';
		if (is_int($value)) {
			assertType('int|string', $data[$key]);
		}
	}
}

/**
 * @param array<int|string> $data
 * @param array<string, mixed> $vars
 */
function extractCall(array $data, array $vars): void
{
	foreach ($data as $key => $value) {
		extract($vars);
		if (is_int($value)) {
			assertType('int|string', $data[$key]);
		}
	}
}

/**
 * @param array<int, int|string> $data
 */
function crossIterationIterateeWrite(array $data): void
{
	foreach ($data as $key => $value) {
		if (is_int($value)) {
			// a previous iteration may have written this element via $data[$key + 1]
			assertType('int|string', $data[$key]);
		}
		$data[$key + 1] = 'str';
	}
}

/**
 * @param array<int, int|string> $data
 */
function crossIterationCallEscape(array $data): void
{
	foreach ($data as $key => $value) {
		if (is_int($value)) {
			assertType('int|string', $data[$key]);
		}
		sort($data);
	}
}

/**
 * The legitimate same-iteration case with no intervening write keeps narrowing.
 *
 * @param array<int|string> $data
 */
function sameIterationNoWrite(array $data): void
{
	foreach ($data as $key => $value) {
		if (is_int($value)) {
			assertType('int', $data[$key]);
		}
	}
}

/**
 * A same-key write is kept - it only touches the current iteration's element.
 *
 * @param array<mixed> $data
 */
function sameKeyWrite(array $data): void
{
	foreach ($data as $key => $value) {
		if (!is_array($value)) {
			continue;
		}
		assertType('array<mixed, mixed>', $data[$key]);
		$data[$key][0] = 'test';
	}
}
