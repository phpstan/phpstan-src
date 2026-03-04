<?php

namespace Bug10657;

class HelloWorld
{
	public function sayHello(bool $flag): void
	{
		for ($i = 0; $i < 10; $i++) {
			if ($flag) {
				$foo = 'bar';
			}
			if ($flag) {
				echo $foo;
			}
		}
	}
}
