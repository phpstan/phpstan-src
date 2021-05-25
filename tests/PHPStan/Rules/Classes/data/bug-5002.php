<?php

namespace Bug5002;

class Foo
{

}

function (): void {
	$name = 'Test\Foo';
	new $name();
};

function (): void {
	$name = '\Test\Foo';
	new $name();
};

function (): void {
	$name = '\\\\Test\Foo';
	new $name();
};

function (): void {
	$name = 'Test\\\\Foo';
	new $name();
};

function (): void {
	$name = '0Test\Foo';
	new $name();
};

function (): void {
	$name = 'Test\0Foo';
	new $name();
};
