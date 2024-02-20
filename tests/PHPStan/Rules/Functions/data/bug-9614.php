<?php declare(strict_types = 1);

namespace Bug9614;

class HelloWorld
{
	public function sayHello(string $key, ?string $a = null, ?string $b = null): string
	{
		$funcs = [
			'test' => function() {
				return 'test';
			},
			'foo' => function($a) {
				return 'foo';
			},
			'bar' => function($a, $b) {
				return 'bar';
			}
		];

        if (!isset($funcs[$key])) {
            return '';
        }

		return $funcs[$key]($a, $b);
	}
}
