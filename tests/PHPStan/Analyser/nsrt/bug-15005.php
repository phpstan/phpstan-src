<?php declare(strict_types = 1);

namespace Bug15005Nsrt;

use function PHPStan\Testing\assertType;

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function nestedIssetLeak(array $r): void
{
	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);

	$port = isset($r['K']['Port']) ? $r['K']['Port'] : null;

	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);

	$secure = $r['K']['Secure'] ?? null;

	echo $port, $secure;
}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function alsoAfterPlainIf(array $r): void
{
	if (isset($r['K']['Port'])) {
		echo $r['K']['Port'];
	}

	assertType('string|null', $r['K']['Secure'] ?? null);
}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function branches(array $r): void
{
	if (isset($r['K']['Port'])) {
		assertType('array{Port: int, Secure: string|null}', $r['K'] ?? null);
	} else {
		assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
	}
	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
}

/** @param array{K?: array{Port: int, Secure: string|null}} $r */
function optionalIntermediateKey(array $r): void
{
	if (isset($r['K']['Port'])) {
		assertType('array{K: array{Port: int, Secure: string|null}}', $r);
	} else {
		assertType('array{}', $r);
	}
	assertType('array{}|array{K: array{Port: int, Secure: string|null}}', $r);
}

/** @param array<string, array<string, array{Port: int}>> $r */
function threeLevels(array $r): void
{
	if (isset($r['A']['B']['Port'])) {
		assertType('array{Port: int}', $r['A']['B'] ?? null);
	} else {
		assertType('array<string, array{Port: int}>|null', $r['A'] ?? null);
		assertType('array<string, array<string, array{Port: int}>>', $r);
	}
}

/** @param array{a: array{x: int}|array{y: int}} $r */
function definitelySetIntermediate(array $r): void
{
	if (isset($r['a']['x'])) {
		assertType('array{x: int}', $r['a']);
	} else {
		assertType('array{y: int}', $r['a']);
	}
}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function nestedEmptyLeak(array $r): void
{
	if (empty($r['K']['Port'])) {
		assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
	}
	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function nestedCoalesceLeak(array $r): void
{
	$port = $r['K']['Port'] ?? null;
	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
	echo $port;
}

/** @param array<int, array{Port: int}> $r */
function intOffsets(array $r): void
{
	if (isset($r[0]['Port'])) {
		assertType('array{Port: int}', $r[0] ?? null);
	} else {
		assertType('array<int<min, -1>|int<1, max>, array{Port: int}>', $r);
		assertType('array{Port: int}|null', $r[1] ?? null);
	}
	assertType('array{Port: int}|null', $r[0] ?? null);
}

/** @param list<array{Port: int}> $r */
function listOffsets(array $r): void
{
	if (isset($r[0]['Port'])) {
		assertType('array{Port: int}', $r[0] ?? null);
	} else {
		assertType('list<array{Port: int}>', $r);
		assertType('array{Port: int}|null', $r[0] ?? null);
	}
	assertType('array{Port: int}|null', $r[0] ?? null);
}

final class Holder
{

	/** @var array<string, array{Port: int, Secure: string|null}> */
	public array $arr = [];

	public function doFoo(): void
	{
		if (isset($this->arr['K']['Port'])) {
			assertType('array{Port: int, Secure: string|null}', $this->arr['K'] ?? null);
		} else {
			assertType('array{Port: int, Secure: string|null}|null', $this->arr['K'] ?? null);
		}
		assertType('array{Port: int, Secure: string|null}|null', $this->arr['K'] ?? null);
	}

}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function multipleIssetVars(array $r): void
{
	if (isset($r['K']['Port'], $r['K']['Secure'])) {
		assertType('array{Port: int, Secure: string}', $r['K']);
	} else {
		assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
	}
	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
}
