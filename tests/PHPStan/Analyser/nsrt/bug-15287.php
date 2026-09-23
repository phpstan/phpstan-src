<?php

namespace Bug15287;

use function PHPStan\Testing\assertType;

function strSplit(string $s, int $i): void
{
	if (PHP_VERSION_ID >= 80200) {
		assertType('array{}', str_split(''));
		assertType('list<non-empty-string>', str_split($s));
		assertType('*NEVER*', str_split($s, 0));
		assertType('list<non-empty-string>', str_split($s, $i));
	} elseif (PHP_VERSION_ID >= 80000) {
		assertType('array{\'\'}', str_split(''));
		assertType('non-empty-list<string>', str_split($s));
		assertType('*NEVER*', str_split($s, 0));
		assertType('non-empty-list<string>', str_split($s, $i));
	} else {
		assertType('array{\'\'}', str_split(''));
		assertType('non-empty-list<string>', str_split($s));
		assertType('false', str_split($s, 0));
		assertType('non-empty-list<string>|false', str_split($s, $i));
	}
}

function mbFunctions(string $s): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('*NEVER*', mb_str_split($s, 1, 'foo'));
		assertType('*NEVER*', mb_strlen($s, 'foo'));
		assertType('*NEVER*', mb_ord($s, 'foo'));
	} else {
		assertType('false', mb_str_split($s, 1, 'foo'));
		assertType('false', mb_strlen($s, 'foo'));
		assertType('false', mb_ord($s, 'foo'));
	}
}

function mbSubstituteCharacter(): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('true', mb_substitute_character(0));
		assertType('true', mb_substitute_character(null));
		assertType('*NEVER*', mb_substitute_character(''));
		assertType('*NEVER*', mb_substitute_character('123'));
		assertType('*NEVER*', mb_substitute_character(0x110000));
		assertType('*NEVER*', mb_substitute_character('foo'));
		assertType('*NEVER*', mb_substitute_character(new \stdClass()));
		assertType("'entity'|'long'|'none'|int<0, 55295>|int<57344, 1114111>", mb_substitute_character());
	} else {
		assertType('false', mb_substitute_character(0));
		assertType('false', mb_substitute_character(null));
		assertType('true', mb_substitute_character(''));
		assertType('true', mb_substitute_character('123'));
		assertType('false', mb_substitute_character('0'));
		assertType('false', mb_substitute_character(0x110000));
		assertType('false', mb_substitute_character('foo'));
		assertType('bool', mb_substitute_character(new \stdClass()));
		assertType("'entity'|'long'|'none'|int<1, 1114111>", mb_substitute_character());
	}
}

function pdoConnect(): void
{
	if (PHP_VERSION_ID >= 80400) {
		assertType('PDO\Sqlite', \PDO::connect('sqlite:foo'));
	}
}
