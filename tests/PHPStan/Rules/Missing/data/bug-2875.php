<?php

namespace Bug2875MissingReturn;

class A {}
class B {}

class HelloWorld
{
	/** @param A|B|null $obj */
	function one($obj): int
	{
		if ($obj === null) return 1;
		else if ($obj instanceof A) return 2;
		else if ($obj instanceof B) return 3;
	}

}
