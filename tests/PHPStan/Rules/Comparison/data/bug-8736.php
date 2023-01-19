<?php

namespace Bug8736;

class HelloWorld
{
	public function sayHello($mixed): void
	{
		if (ctype_digit((string) $mixed)) {
			if ($mixed === 1) {
				echo '$mixed is 1';
			}
		}
	}
}
