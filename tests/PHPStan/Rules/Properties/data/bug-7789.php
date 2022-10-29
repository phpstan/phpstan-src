<?php

namespace Bug7789;

class HelloWorld
{
	/** @var '"'|'\'' */
	public static $u;
}

function (): void {
	HelloWorld::$u = '"';
	HelloWorld::$u = '\'';
};
