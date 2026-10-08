<?php declare(strict_types = 1);

namespace Bug15428Rule;

/**
 * @param array{a?: string, ...} $c
 * @param array{a?: string} $d
 */
function repro(array $c, array $d): void
{
	if (!isset($c['a'])) {
		isset($c['a']);
	}
	if (!isset($d['a'])) {
		isset($d['a']);
	}
}
