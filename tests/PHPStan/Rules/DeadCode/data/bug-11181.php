<?php

namespace Bug11181;

function foo(): \Iterator
{
	return;
	yield;
}

function bar(): \Generator
{
	return;
	yield;
}

function baz(): iterable
{
	return;
	yield;
}
