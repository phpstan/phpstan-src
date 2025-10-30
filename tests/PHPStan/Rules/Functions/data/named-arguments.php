<?php

namespace FunctionNamedArguments;

function bar(): void
{
	foo(i: 1);
	foo(i: 1, j: 2, z: 3);
}

function baz(): void
{
	variadicFunction(...['a' => ['b', 'c']]); // works - userland
	array_merge(...['a' => ['b', 'c']]); // doesn't work - internal
}

function bug13710(): void
{
	variadicFunction(var: 1, var: 2);
}
