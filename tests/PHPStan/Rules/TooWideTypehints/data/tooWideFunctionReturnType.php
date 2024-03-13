<?php

namespace TooWideFunctionReturnType;

function foo(): \Generator {
	yield 1;
	yield 2;
	return 3;
}

function bar(): ?string {
	return null;
}

function baz(): ?string {
	return 'foo';
}

function lorem(): ?string {
	if (rand(0, 1)) {
		return '1';
	}

	return null;
}

function ipsum(): ?string {
	$f = function () {
		return null;
	};

	$c = new class () {
		public function doFoo() {
			return null;
		}
	};

	return 'str';
}

function dolor2(): ?string {
	if (rand()) {
		return 'foo';
	}
}

/**
 * @return string|null
 */
function dolor3() {
	if (rand()) {
		return 'foo';
	}
}

/**
 * @return string|int
 */
function dolor4() {
	if (rand()) {
		return 'foo';
	}
}

/**
 * @return string|null
 */
function dolor5() {
	if (rand()) {
		return 'foo';
	}

	return null;
}

/**
 * @return string|null
 */
function dolor6() {
	if (rand()) {
		return 'foo';
	}

	return 'bar';
}

/**
 * @return ($flag is 1 ? string : int)
 */
function conditionalType(int $flag)
{
	return $flag;
}
