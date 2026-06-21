<?php

namespace Bug14848;

use function PHPStan\Testing\assertType;

function reassignedAfter(): void
{
	$tmpdir = "";
	$myfunc = function () use (&$tmpdir) {
		assertType('string', $tmpdir);
	};

	$tmpdir = "/tmp/my/useful/tempdir";
	$myfunc();
}

function notReassigned(): void
{
	$tmpdir = "";
	$myfunc = function () use (&$tmpdir) {
		assertType("''", $tmpdir);
	};

	$myfunc();
}

function reassignedBeforeOnly(): void
{
	$tmpdir = "";
	$tmpdir = "foo";
	$myfunc = function () use (&$tmpdir) {
		assertType("'foo'", $tmpdir);
	};

	$myfunc();
}

function reassignedAfterWithDifferentType(): void
{
	$x = 1;
	$myfunc = function () use (&$x) {
		assertType('int', $x);
	};

	$x = 5;
	$myfunc();
}

function assignOpAfter(): void
{
	$s = "a";
	$myfunc = function () use (&$s) {
		assertType('string', $s);
	};

	$s .= "b";
	$myfunc();
}

function incrementAfter(): void
{
	$i = 0;
	$myfunc = function () use (&$i) {
		assertType('int', $i);
	};

	$i++;
	$myfunc();
}

function reassignedInNestedBlockAfter(): void
{
	$v = 1;
	$myfunc = function () use (&$v) {
		assertType('int', $v);
	};

	if (rand(0, 1)) {
		$v = 2;
	}
	$myfunc();
}

function arrayElementReassignedAfter(): void
{
	$arr = ['a'];
	$myfunc = function () use (&$arr) {
		assertType('non-empty-list<string>', $arr);
	};

	$arr[] = 'b';
	$myfunc();
}

// not reassigned -> value used inside is kept precise, body modifications still tracked
function bodyModificationsStillTracked(): void
{
	$counter = 0;
	$myfunc = function () use (&$counter) {
		assertType('int<0, max>', $counter);
		$counter++;
	};

	$myfunc();
}
