<?php

namespace ClosureReturnTypes;

use SomeOtherNamespace\Baz;

function () {
	return 1;
};
function () {
	return 'foo';
};
function () {
	return;
};

function (): int {
	return 1;
};
function (): int {
	return 'foo';
};

function (): string {
	return 'foo';
};
function (): string {
	return 1;
};

function (): Foo {
	return new Foo();
};
function (): Foo {
	return new Bar();
};

function (): \SomeOtherNamespace\Foo {
	return new Foo();
};
function (): \SomeOtherNamespace\Foo {
	return new \SomeOtherNamespace\Foo();
};

function (): Baz {
	return new Foo();
};
function (): Baz {
	return new Baz();
};

function (): \Traversable {
	/** @var int[]|\Traversable $foo */
	$foo = doFoo();
	return $foo;
};

function (): \Generator {
	yield 1;
	return;
};

function () {
	if (rand(0, 1)) {
		return;
	}
};

function () {
	if (rand(0, 1)) {
		return null;
	}
};

function () {
	if (rand(0, 1)) {
		return [];
	}

	return; // OK
};

function (): ?array {
	if (rand(0, 1)) {
		return [];
	}

	return; // report
};

function (): string {
	if (rand(0, 1)) {
		return 'foo';
	}

	function (): int {
		return 1;
	};

	return 'bar';
};
