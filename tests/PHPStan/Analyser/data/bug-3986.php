<?php

namespace Bug3986;

use function PHPStan\Analyser\assertType;

interface Boo {
	public function nullable(): ?int;
}

class HelloWorld
{
	public function sayHello(Boo $value): void
	{
		$result = $value->nullable();
		$isNotNull = $result !== null;

		if ($isNotNull) {
			assertType('int', $result);
		}
		if ($result !== null) {
			assertType('int', $result);
		}
	}
}
