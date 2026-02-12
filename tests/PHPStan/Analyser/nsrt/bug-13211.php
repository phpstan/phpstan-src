<?php

namespace Bug13211;

use function PHPStan\Testing\assertType;

class Foo
{
	public function bar(): void
	{
	}
}

function switchTrueWithExitNarrows(): void
{
	$a = random_int(1, 10) > 5 ? new Foo() : null;

	switch (true) {
		case $a === null:
			exit;
	}

	assertType('Bug13211\Foo', $a);
}

function switchTrueWithReturnNarrows(): void
{
	$a = random_int(1, 10) > 5 ? new Foo() : null;

	switch (true) {
		case $a === null:
			return;
	}

	assertType('Bug13211\Foo', $a);
}

function switchTrueWithThrowNarrows(): void
{
	$a = random_int(1, 10) > 5 ? new Foo() : null;

	switch (true) {
		case $a === null:
			throw new \Exception();
	}

	assertType('Bug13211\Foo', $a);
}

function switchTrueMultipleCases(): void
{
	/** @var int|string|null $a */
	$a = null;

	switch (true) {
		case $a === null:
			exit;
		case is_string($a):
			exit;
	}

	assertType('int', $a);
}

function switchTrueWithInstanceof(): void
{
	/** @var Foo|int|null $a */
	$a = null;

	switch (true) {
		case $a instanceof Foo:
			exit;
	}

	assertType('int|null', $a);
}

function switchTrueWithBreakDoesNotNarrow(): void
{
	$a = random_int(1, 10) > 5 ? new Foo() : null;

	switch (true) {
		case $a === null:
			break;
	}

	assertType('Bug13211\Foo|null', $a);
}

function switchTrueWithDefaultCase(): void
{
	$a = random_int(1, 10) > 5 ? new Foo() : null;

	switch (true) {
		case $a === null:
			exit;
		default:
			break;
	}

	assertType('Bug13211\Foo', $a);
}

function regularSwitchStillWorks(): void
{
	/** @var 1|2|3 $a */
	$a = 1;

	switch ($a) {
		case 1:
			exit;
	}

	assertType('2|3', $a);
}
