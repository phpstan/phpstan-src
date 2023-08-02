<?php

namespace PregGrep;

use function PHPStan\Testing\assertType;

function compilablePattern(array $a) {
	assertType('array', preg_grep('/^[0-9]+$/', $a));
}

function bogusPattern(array $a) {
	assertType('array|false', preg_grep('/bogus-pattern-[0^-9]$/', $a));
}

function unknownPattern(string $p, array $a) {
	assertType('array|false', preg_grep($p, $a));
}

function sometimesCompilablePattern(array $a) {
	$p = '/^[0-9]+$/';
	if (rand(0,1)) {
		$p = '/bogus-pattern-[0^-9]$/';
	}
	assertType('array|false', preg_grep($p, $a));
}
