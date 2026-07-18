<?php

namespace Bug9966;

/** @param array{key1: string, key2: string|null, key3?: string, key4?: string|null} $a */
function doFoo(array $a): void
{
	// always exists and is not nullable - existing report
	echo $a['key1'] ?? null;

	// always exists but nullable - unnecessary `?? null`
	echo $a['key2'] ?? null;

	// might be undefined - not reported
	echo $a['key3'] ?? null;

	// might be undefined - not reported
	echo $a['key4'] ?? null;
}
