<?php declare(strict_types = 1);

function do0()
{
	/** @var list<string> */
	return [0 => 'a', 1 => 'b', 2 => 'c'];
}

function do1()
{
	/** @var list<string> */
	return [1 => 'b', 2 => 'c'];
}

function do2()
{
	/** @var list<string> */
	return [0 => 'a', 2 => 'c'];
}

function do3()
{
	/** @var list<string> */
	return [-1 => 'z', 0 => 'a', 1 => 'b', 2 => 'c'];
}

function do4()
{
	/** @var list<string> */
	return [0 => 'a', -1 => 'z', 1 => 'b', 2 => 'c'];
}
