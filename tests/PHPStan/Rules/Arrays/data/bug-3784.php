<?php

namespace Bug3784;

class HelloWorld
{
	public function sayHello(string $value): void
	{
		$parsedValue = parse_url($value);

		if (false === $parsedValue) {
			return;
		}

		foreach (['host'] as $key) {
			if (empty($parsedValue[$key])) {
				return;
			}
		}

		var_dump($parsedValue['host']);
	}
}
