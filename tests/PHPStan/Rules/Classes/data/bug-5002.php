<?php

namespace Test;

class Foo
{

}

function (): void {
	$name = '\Test\Foo';
	new $name();
};

function (): void {
	$name = '\\Test\Foo';
	new $name();
};
