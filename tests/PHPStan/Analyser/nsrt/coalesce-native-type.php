<?php declare(strict_types = 1);

namespace CoalesceNativeType;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @return mixed */
function fetchMixed()
{
	return null;
}

function coalesce(): void
{
	/** @var non-empty-string|null $x */
	$x = fetchMixed();
	assertType('non-empty-string', $x ?? 'fallback');
	assertNativeType('mixed~null', $x ?? 'fallback');
}

function coalesceAssign(): void
{
	/** @var non-empty-string|null $y */
	$y = fetchMixed();
	$y ??= 'fallback';
	assertType('non-empty-string', $y);
	assertNativeType('mixed~null', $y);
}
