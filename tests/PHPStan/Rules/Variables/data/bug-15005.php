<?php declare(strict_types = 1);

namespace Bug15005;

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function nestedIssetLeak(array $r): void
{
	$port = isset($r['K']['Port']) ? $r['K']['Port'] : null;

	$secure = $r['K']['Secure'] ?? null;

	echo $port, $secure;
}

/** @param array<string, array{Port: int, Secure: string|null}> $r */
function alsoAfterPlainIf(array $r): void
{
	if (isset($r['K']['Port'])) {
		echo $r['K']['Port'];
	}

	$secure = $r['K'] ?? null;

	echo count((array) $secure);
}
