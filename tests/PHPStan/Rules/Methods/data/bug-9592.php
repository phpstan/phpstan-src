<?php declare(strict_types = 1);

namespace Bug9592;

class rex_response {}

class HelloWorld
{
	public function sayHello(): void
	{
		// method_exists with string literal, then static call
		$nonce = method_exists('Bug9592\rex_response', 'getNonce') ? rex_response::getNonce() : '';
	}

	public function sayHello2(): void
	{
		if (!method_exists('Bug9592\rex_response', 'getNonce')) {
			return;
		}

		rex_response::getNonce();
	}

	public function sayHello3(): void
	{
		if (method_exists('Bug9592\rex_response', 'getNonce')) {
			rex_response::getNonce();
		}
	}
}
