<?php

declare(strict_types = 1);

namespace Bug14170;

use function PHPStan\Testing\assertType;

/**
 * @param iterable<string, list<string>> $convert
 * @param-out iterable<string, list<string>> $convert
 */
function example3a(iterable &$convert): void
{
	foreach ($convert as &$inner) {
		foreach ($inner as &$val) {
			$val = strtoupper($val);
		}
	}
	assertType('iterable<string, list<string>>', $convert);
}

/**
 * @param iterable<string, list<string>> $convert
 * @param-out iterable<string, list<string>> $convert
 */
function example3b(iterable &$convert): void
{
	foreach ($convert as $outerKey => $inner) {
		foreach ($inner as $key => $val) {
			$convert[$outerKey][$key] = strtoupper($val);
		}
	}
	assertType('iterable<string, list<string>>', $convert);
}
