<?php

namespace Bug11617;

class HelloWorld
{
	/**
	 * @var array<string, string>
	 */
	private $params;

	public function sayHello(string $query): void
	{
		\parse_str($query, $this->params);
		\parse_str($query, $tmp);
		$this->params = $tmp;

		/** @var array<string, string> $foo */
		$foo = [];
		\parse_str($query, $foo);
		$this->params = $foo;
	}
}
