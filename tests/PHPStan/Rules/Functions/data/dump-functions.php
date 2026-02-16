<?php

namespace DumpFunctions;

use function PHPStan\dumpType;
use function PHPStan\dumpNativeType;
use function PHPStan\dumpPhpdocType;

class HelloWorld
{
	public function sayHello(): void
	{
		dumpType();
		dumpNativeType();
		dumpPhpdocType();

		dumpType(1, 2, 3);
		dumpNativeType(1, 2, 3);
		dumpPhpdocType(1, 2, 3);
	}
}
