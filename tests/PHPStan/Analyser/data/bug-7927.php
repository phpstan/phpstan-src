<?php declare(strict_types = 1);

namespace Bug7927;

enum Test : int
{
	case One;
	case Two;
}

$v = Test::One;

function doIt(Test $v) : string
{
	switch($v)
	{
		case Test::One:
			return "One";
		case Test::Two:
			return "Two";
		default:
			throw new \ErrorException("Unknown '{$v->name}'.");
	}
}

echo doIt($v);
