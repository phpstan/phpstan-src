<?php

namespace FunctionNamedArguments;

function bar(): void
{
	foo(i: 1);
	foo(i: 1, j: 2, z: 3);
}
