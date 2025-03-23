<?php // lint >= 8.0

namespace ImpossibleInstanceofNewIsAlwaysFinal;

interface Foo
{

}

class Bar
{

}

function (): void {
	$bar = new Bar();
	if ($bar instanceof Foo) {

	}
};

function (Bar $bar): void {
	if ($bar instanceof Foo) {

	}
};

function (Bar $bar): void {
	if ($bar::class !== Bar::class) {
		return;
	}

	if ($bar instanceof Foo) {

	}
};

function (Bar $bar): void {
	if (Bar::class !== $bar::class) {
		return;
	}

	if ($bar instanceof Foo) {

	}
};

function (Bar $bar): void {
	if (get_class($bar) !== Bar::class) {
		return;
	}

	if ($bar instanceof Foo) {

	}
};

function (Bar $bar): void {
	if (Bar::class !== get_class($bar)) {
		return;
	}

	if ($bar instanceof Foo) {

	}
};

function (): void {
	$bar = null;
	if (rand(0,1)===1) {
		$bar = new Bar();
	}
	if ($bar instanceof Foo) {

	}
};

class Baz extends Bar
{

}

function (): void {
	$bar = null;
	if (rand(0,1)===1) {
		$bar = new Bar();
	}
	if ($bar instanceof Baz) {

	}
};
