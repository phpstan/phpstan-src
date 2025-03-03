<?php

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
