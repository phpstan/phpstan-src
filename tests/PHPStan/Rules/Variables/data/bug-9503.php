<?php declare(strict_types=1);

namespace Bug9503;

class HelloWorld
{
	public function sayHello(string $str): bool
	{
		if (preg_match('~x(a)?(b)?~', $str, $matches) > 0) {
			if (isset($matches[2]) && preg_match('~x2(a)?(b)?~', $matches[2], $matches) > 0) {
				if (isset($matches[2])) {
					return true;
				}
			}
		}

		return false;
	}
}
