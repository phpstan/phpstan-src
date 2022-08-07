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


function testNoBreak($param): void
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
}
