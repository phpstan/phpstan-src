<?php

namespace Bug2434;

function foo(int $param): void
{
	//do something
}

function fooWithoutVoid(int $param)
{

}

function (): void {
	register_shutdown_function('Bug2434\\foo', 1);
	register_shutdown_function('Bug2434\\fooWithoutVoid', 1);

	$parameter = new \stdClass();

	$shutdown = static function (\stdClass $parameter): void {};

	register_shutdown_function($shutdown, $parameter);
};
