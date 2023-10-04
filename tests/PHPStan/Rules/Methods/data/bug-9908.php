<?php

namespace Bug9908;

class HelloWorld
{
	public function test(): void
	{
		$a = [];
		if (rand() % 2) {
			$a = ['bar' => 'string'];
		}

		if (isset($a['bar'])) {
			$a['bar'] = 1;
		}

		$this->sayHello($a);
	}

	/**
	 * @param array{bar?: int} $foo
	 */
	public function sayHello(array $foo): void
	{
		echo 'Hello' . print_r($foo, true);
	}
}
