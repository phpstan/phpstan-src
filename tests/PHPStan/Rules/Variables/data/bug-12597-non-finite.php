<?php declare(strict_types = 1);

namespace Bug12597NonFinite;

class HelloWorld
{
	public function test(mixed $type): void
	{
		if (is_int($type) || is_string($type)) {
			$message = 'Hello!';
		}

		if (is_int($type)) {
			$this->message($message);
		}
	}

	public function message(string $message): void {}
}
