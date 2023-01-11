<?php declare(strict_types = 1);

namespace Bug4335;

class HelloWorld
{
	public function sayHello(): void
	{
		foreach (class_implements($this) as $k => $v) {
			var_dump($k, $v);
		}
		foreach (class_parents($this) as $k => $v) {
			var_dump($k, $v);
		}
		foreach (class_uses($this) as $k => $v) {
			var_dump($k, $v);
		}
	}
}
