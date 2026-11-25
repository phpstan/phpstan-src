<?php declare(strict_types = 1);

namespace VaryingExpressionsNotRemembered;

use function PHPStan\Testing\assertType;

/**
 * @param \Generator<int, int, int, int> $inner
 * @param array<int, int> $arr
 * @return \Generator<int, int, int, int>
 */
function yields(\Generator $inner, array $arr): \Generator
{
	if ((yield 1) === 5) {
		assertType('int', yield 1);
	}
	if (yield 1) {
		assertType('int', yield 1);
	}
	if ((yield from $inner) === 5) {
		assertType('int', yield from $inner);
	}
	if ($arr[yield 1] === 5) {
		assertType('int', $arr[yield 1]);
	}

	return 1;
}

function shellExec(): void
{
	if (`ls` === 'a') {
		assertType('string|false|null', `ls`);
	}
	if (`ls`) {
		assertType('string|false|null', `ls`);
	}
	if ('a' . `ls` === 'ab') {
		assertType('non-falsy-string', 'a' . `ls`);
	}
}

/** @param array<int, int> $arr */
function increments(int $i, string $s, array $arr): void
{
	if ($i++ === 5) {
		assertType('int', $i++);
	}
	if ($i++) {
		assertType('int', $i++);
	}
	if (++$i === 5) {
		assertType('int', ++$i);
	}
	if (++$i) {
		assertType('int', ++$i);
	}
	if ($i-- === 5) {
		assertType('int', $i--);
	}
	if (--$i === 5) {
		assertType('int', --$i);
	}
	if (($i += 1) === 5) {
		assertType('int', $i += 1);
	}
	if (($s .= 'a') === 'aa') {
		assertType('non-falsy-string', $s .= 'a');
	}
	if ($arr[$i++] === 5) {
		assertType('int', $arr[$i++]);
	}
	if ('a' . $i++ === 'a5') {
		assertType('lowercase-string&non-falsy-string', 'a' . $i++);
	}
	if ($i++ + 1 === 5) {
		assertType('int', $i++ + 1);
	}
	if (abs($i++) === 5) {
		assertType('int<0, max>', abs($i++));
	}
}

/** @param array<int, int> $arr */
function coalesceAssign(?int $i, array $arr): void
{
	if (($i ??= 1) === 5) {
		assertType('5', $i);
	}
	if ($arr[$i ??= 1] === 5) {
		assertType('5', $arr[$i ??= 1]);
	}
}
