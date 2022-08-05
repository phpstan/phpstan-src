<?php

namespace Bug5370;

class HelloWorld
{
	public function sayHello(string $set): void
	{
		// fallback
		if ($set == 'false' || !$set) {
			$set = '0';
		}

		if ($set) {
			echo "hello";
		}
	}
}
