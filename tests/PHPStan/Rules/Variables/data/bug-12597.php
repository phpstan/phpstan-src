<?php

declare(strict_types = 1);

namespace Bug12597;

class HelloWorld
{
	private const TYPE_1 = 1;
	private const TYPE_2 = 2;

	public function test(int $type): void
	{
		if (in_array($type, [self::TYPE_1, self::TYPE_2], true)) {
			$message = 'Hello!';
		}

		if ($type === self::TYPE_1) {
			$this->message($message);
		}
	}

	public function message(string $message): void {}
}
