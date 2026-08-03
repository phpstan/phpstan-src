<?php declare(strict_types = 1);

namespace Bug15005;

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

	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function notWithCoalesce(array $r): void
{
	$port = $r['K']['Port'] ?? null;
	assertType('array{Port: int, Secure: string|null}|null', $r['K'] ?? null);
	echo $port;
}

/** @param array<string, string|null> $r */
function notWithSingleLevel(array $r): void
{
	$port = isset($r['K']) ? $r['K'] : null;
	assertType('string|null', $r['K'] ?? null);
	echo $port;
}

/** @param array<string, array<string, array{Port: int}>> $r */
function threeLevels(array $r): void
{
	if (isset($r['A']['B']['Port'])) {
		echo $r['A']['B']['Port'];
	}

	assertType('array{Port: int}|null', $r['A']['B'] ?? null);
	assertType('array<string, array{Port: int}>|null', $r['A'] ?? null);
}

class Holder
{

	/** @var array<string, array{Port: int, Secure: string|null}> */
	public array $arr = [];

	public function doFoo(): void
	{
		if (isset($this->arr['K']['Port'])) {
			echo $this->arr['K']['Port'];
		}

		assertType('array{Port: int, Secure: string|null}|null', $this->arr['K'] ?? null);
	}

}
