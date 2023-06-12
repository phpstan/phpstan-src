<?php declare(strict_types = 1);

namespace Bug8319;

foo();

function foo(): never
{
	\var_dump('reachable statement!');
	exit();
}
