<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15754;

use function PHPStan\Testing\assertType;

function f(?string $s): void { $s = strlen($s ?: "") ?: 0; }

function g(?string $s): void
{
	$s = strlen($s ?: "") ?: 0;
	assertType('int<0, max>', $s);
	if ($s === 0) {
		assertType('0', $s);
	} else {
		assertType('int<1, max>', $s);
	}
}

function h(?string $businessTypeIds): void
{
	$businessTypeIds = preg_split('/,/', $businessTypeIds ?: '', flags: PREG_SPLIT_NO_EMPTY) ?: [];
	assertType('list<non-empty-string>', $businessTypeIds);
}

final class Foo
{

	public ?int $p = null;

	public ?Foo $next = null;

}

function i(Foo $f): void
{
	$f = $f->p !== null ? $f->next : null;
	if ($f !== null) {
		assertType('int|null', $f->p);
	}
}

function j(Foo $f): void
{
	$f = $f->p === null ? null : $f->next;
	if ($f !== null) {
		assertType('int|null', $f->p);
	}
}
