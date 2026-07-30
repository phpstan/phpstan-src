<?php declare(strict_types = 1);

namespace Bug15021;

/** @param array{foo?: string, bar?: string} $data */
function foo(array $data): void {
	$data['foo'] ??= $data['bar'] ?? null;
}
