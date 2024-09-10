<?php

namespace Bug11655;

class Foo
{
	public function test(string $v): string
	{
		if (preg_match('~a(x)~', $v, $matches) === 1) {
			if (preg_match('~b(x)~', $v, $matches[2]) === 1) {
				return $matches[2][1];
			}

			if (preg_match('~c(x)~', $v, $matches[2]) === 1) {
				return $matches[3][1];
			}

			return $matches[1];
		}

		return 'n/a';
	}
}
