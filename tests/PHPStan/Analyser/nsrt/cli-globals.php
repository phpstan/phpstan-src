<?php

namespace CliGlobals;

use function PHPStan\Testing\assertType;

assertType('int<1, max>', $argc);
assertType('non-empty-list<string>', $argv);

function f() {
	assertType('*ERROR*', $argc);
	assertType('*ERROR*', $argv);
}

function g($argc, $argv) {
	assertType('mixed', $argc);
	assertType('mixed', $argv);
}

function h() {
	global $argc, $argv;
	assertType('int<1, max>', $argc);
	assertType('non-empty-list<string>', $argv);
}

function i() {
	// user created local variable
	$argc = 'hallo';
	$argv = 'welt';

	assertType("'hallo'", $argc);
	assertType("'welt'", $argv);
}

function j() {
	global $argc, $argv;
	assertType('int<1, max>', $argc);
	assertType('non-empty-list<string>', $argv);

	$argc = 'overridden';
	assertType("'overridden'", $argc);
}

class Foo {
	public function bar(): void {
		global $argc, $argv;
		assertType('int<1, max>', $argc);
		assertType('non-empty-list<string>', $argv);
	}

	public static function baz(): void {
		global $argc, $argv;
		assertType('int<1, max>', $argc);
		assertType('non-empty-list<string>', $argv);
	}
}

function withClosure(): void {
	$fn = function () {
		global $argc, $argv;
		assertType('int<1, max>', $argc);
		assertType('non-empty-list<string>', $argv);
	};
}
