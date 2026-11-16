<?php // lint >= 8.0

namespace Bug15754;

function f(?string $s): void { $s = strlen($s ?: "") ?: 0; }

/**
 * @return list<string>
 */
function splitIds(?string $ids): array
{
	$ids = preg_split('/,/', $ids ?: '', flags: PREG_SPLIT_NO_EMPTY) ?: [];
	foreach ($ids as $id) {
		echo $id;
	}

	return $ids;
}

function twice(?string $s): int
{
	$s = strlen($s ?: "") ?: 0;
	$s = strlen($s ?: "") ?: 0;

	return $s;
}
