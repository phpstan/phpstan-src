<?php declare(strict_types = 1);

namespace Bug12012;

class HelloWorld
{
	public function sayHello(): void
	{
		$s1 = '';
		$s1 .= '<h1>text</h1>';

		$s1 = 'something else';
	}
}
