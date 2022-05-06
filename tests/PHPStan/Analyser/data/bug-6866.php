<?php

namespace Bug6866;

function f(): string
{
	// This would hit the memory limit if it was resolved as a ConstantStringType
	return str_repeat('abcdefghij', 1000000000);
}
