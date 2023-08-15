<?php

namespace PregMatching;

use function PHPStan\Testing\assertType;

function compilablePattern(array $a) {
	assertType('array', preg_grep('/^[0-9]+$/', $a));
	assertType('0|1', preg_match('/^[0-9]+$/', $a));
	assertType('int<0, max>', preg_match_all('/^[0-9]+$/', $a));
	assertType('list<string>', preg_split('/^[0-9]+$/', $a));
}

function bogusPattern(array $a) {
	assertType('array|false', preg_grep('/bogus-pattern-[0^-9]$/', $a));
	assertType('0|1|false', preg_match('/bogus-pattern-[0^-9]$/', $a));
	assertType('int<0, max>|false', preg_match_all('/bogus-pattern-[0^-9]$/', $a));
	assertType('list<string>|false', preg_split('/bogus-pattern-[0^-9]$/', $a));
}

function unknownPattern(string $p, array $a) {
	assertType('array|false', preg_grep($p, $a));
	assertType('0|1|false', preg_match($p, $a));
	assertType('int<0, max>|false', preg_match_all($p, $a));
	assertType('list<string>|false', preg_split($p, $a));
}

function sometimesCompilablePattern(array $a) {
	$p = '/^[0-9]+$/';
	if (rand(0,1)) {
		$p = '/bogus-pattern-[0^-9]$/';
	}
	assertType('array|false', preg_grep($p, $a));
	assertType('0|1|false', preg_match($p, $a));
	assertType('int<0, max>|false', preg_match_all($p, $a));
	assertType('list<string>|false', preg_split($p, $a));
}
