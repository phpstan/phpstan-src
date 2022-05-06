<?php // lint >= 7.4

namespace Bug5969;

class HelloWorld
{
	public string $date;

	public function sayHello(object $o): void
	{
		if (! isset($o->date)) return;

		if (strtotime($o->date) < time()) echo "a";

		// surprisingly this is not an issue
		if (strtotime($this->date) < time()) echo "b";

		if (is_string($o->date) && strtotime($o->date) < time()) echo "c";
	}
}
