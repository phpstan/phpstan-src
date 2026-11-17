<?php declare(strict_types = 1);

namespace Bug15748;

use function PHPStan\Testing\assertType;

function foo(?string $x) {
	if (($x ?? '') == '')
		return null;
	assertType('non-empty-string', $x);
	$y = explode(':', $x);
	// ...
}

function bar(?string $x) {
	if (($x ?? '') != '') {
		assertType('non-empty-string', $x);
	}
}

function baz(?string $x) {
	if (($x ?? '') === '')
		return null;
	assertType('non-empty-string', $x);
}

function qux(?string $x) {
	if (($x ?? '') !== '') {
		assertType('non-empty-string', $x);
	}
}

function intCase(?int $x) {
	if (($x ?? 0) == 0)
		return null;
	assertType('int<min, -1>|int<1, max>', $x);
}

function intStrictCase(?int $x) {
	if (($x ?? 0) === 0)
		return null;
	assertType('int<min, -1>|int<1, max>', $x);
}

class Foo
{
	public ?string $bar = null;
}

/** @param array{k?: string|null} $a */
function analogous(array $a, ?string $x, ?int $i, ?bool $b, ?Foo $o) {
	if (($a['k'] ?? '') !== '') {
		assertType('array{k: non-empty-string}', $a);
	}
	if (($i ?? 0) > 0) {
		assertType('int<1, max>', $i);
	}
	if (($b ?? false) !== false) {
		assertType('true', $b);
	}
	if (!in_array($x ?? '', ['', 'foo'], true)) {
		assertType('non-empty-string', $x);
	}
	if (($o?->bar ?? '') !== '') {
		assertType('Bug15748\Foo', $o);
		assertType('non-empty-string', $o->bar);
	}
	if (($x ?? 'a') === null) {
		assertType('*NEVER*', $x);
	}
	if (($x ?? '') !== 'abc') {
		assertType('string|null', $x);
	}
}

/** @param int|string|null $z */
function typeSpecifyingExtensions(?string $x, $z) {
	if (is_callable($x ?? 1)) {
		assertType('callable-string', $x);
	}
	if (is_callable($x ?? 'strlen') !== true) {
		assertType('string', $x);
	}
	if (!is_array($z ?? [])) {
		assertType('int|string', $z);
	}
}

function negatedTrueContext(?string $x, bool $c) {
	if ((($x ?? '') === '') !== true) {
		assertType('non-empty-string', $x);
	}
	$w = $c ? '' : ($x ?? '');
	if ($w !== '') {
		assertType('non-empty-string', $x);
	}
}
