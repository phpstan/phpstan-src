<?php

namespace SwitchScope;


use function PHPStan\Testing\assertType;

function foo($y)
{
	switch (gettype($y)) {
		case "integer": {
			assertType("'integer'", gettype($y));
		}
	}
}

function test($param): void
{
	switch ($param) {
		case 'a':
		{
			assertType("'a'", $param);
			break;
		}
		case 'b':
		{
			assertType("'b'", $param);
			break;
		}
		default:
		{
			assertType("mixed", $param);
		}
	}
	assertType("mixed", $param);
}


function testNoBreak($param, string $s, int $i): void
{
	switch ($param) {
		case 'a':
		{
			assertType("'a'", $param);
		}
		case 'b':
		{
			assertType("'a'|'b'", $param);
		}
		default:
		{
			assertType("mixed", $param);
		}
	}
	assertType("mixed", $param);

	switch ($s) {
		case 'a':
		case 'b':
		case 'c':
		case 'd':
		{
			assertType("'a'|'b'|'c'|'d'", $s);
		}
		default:
		{
			assertType("string", $s);
		}
	}
	assertType("string", $s);

	switch ($i)	{
		case 0:
			assertType("0", $i);
		default: {
			assertType("int", $i);

		}
	}
	assertType("int", $i);
}

function switchTrue($mixed) {
	switch(true)
	{
		case $mixed === 2:
			assertType("2", $mixed);
			break;
		case is_array($mixed):
			assertType("array", $mixed);
			break;
		case is_int($mixed):
			assertType("int<min, 1>|int<3, max>", $mixed);
			break;
		default:
			assertType("mixed~2|array", $mixed); // should be "mixed~int|array"
	}
	assertType("mixed", $mixed);
}
