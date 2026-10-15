<?php declare(strict_types = 1);

namespace Bug15404;

/** @param array{projects?: list<string>} $s */
function a(array $s): void
{
	$has = isset($s['projects']);
	if (!$has) {
		return;
	}
	foreach ($s['projects'] as $p) {
		if (!is_string($p)) {
			throw new \InvalidArgumentException();
		}
	}
}
