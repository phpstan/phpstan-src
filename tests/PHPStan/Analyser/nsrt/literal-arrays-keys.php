<?php

namespace LiteralArrayKeys;

use function PHPStan\Testing\assertType;

define('STRING_ONE', '1');
define('INT_ONE', 1);
define('STRING_FOO', 'foo');

class Foo
{

	public function getString(): string
	{
		return '1';
	}

}

function () {
	$foo = new Foo();

	foreach ([
		'one',
		'two',
		'three',
	] as $key => $value) {
		assertType('0|1|2', $key);
	}

	foreach ([
		0 => 'one',
		'two',
		'three',
	] as $key => $value) {
		assertType('0|1|2', $key);
	}


	foreach ([
		'foo' => 'one',
		'two',
		'three',
	] as $key => $value) {
		assertType('0|1|\'foo\'', $key);
	}

	foreach ([
		'1' => 'one',
		'two',
		'three',
	] as $key => $value) {
		assertType('1|2|3', $key);
	}

	foreach ([
		'1' => 'one',
		'2' => 'two',
		\STRING_ONE => 'three',
	] as $key => $value) {
		assertType('1|2', $key);
	}

	foreach ([
		1 => 'one',
		2 => 'two',
		\INT_ONE => 'three',
	] as $key => $value) {
		assertType('1|2', $key);
	}

	foreach ([
		1 => 'one',
		2.5 => 'two',
		3.2 => 'three',
	] as $key => $value) {
		assertType('1|2|3', $key);
	}

	foreach ([
		'foo' => 'one',
		'bar' => 'two',
		\STRING_FOO => 'three',
	] as $key => $value) {
		assertType('\'bar\'|\'foo\'', $key);
	}

	foreach ([
		null => 'one',
		'bar' => 'two',
		'baz' => 'three',
	] as $key => $value) {
		assertType('\'\'|\'bar\'|\'baz\'', $key);
	}

	foreach ([
		1 => 'one',
		2 => 'two',
		$foo->getString() => 'three',
	] as $key => $value) {
		assertType('1|2|string', $key);
	}

	foreach ([
		1 => 'one',
		2 => 'two',
		'foo' => 'three',
	] as $key => $value) {
		assertType('1|2|\'foo\'', $key);
	}

	foreach ([
		true => 'one',
		false => 'two',
	] as $key => $value) {
		assertType('0|1', $key);
	}

	foreach ([
		1 => 'one',
		2 => 'two',
		'foo' => 'three',
	] as $key => $value) {
		assertType('1|2|\'foo\'', $key);
	}

	foreach ([
		UNKNOWN_CONSTANT => 'one',
		2 => 'two',
		'foo' => 'three',
	] as $key => $value) {
		assertType('(int|string)', $key);
	}
};
