<?php declare(strict_types = 1);

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

class HelloWorld2
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

class Foo {}
class Bar {}

class HelloWorld3
{
	public function test(mixed $type): void
	{
		if (is_int($type) || is_object($type)) {
			$message = 'Hello!';
		}

		if (is_int($type)) {
			$this->message($message);
		}
	}

	public function test2(mixed $type): void
	{
		if ($type instanceof Foo || $type instanceof Bar) {
			$message = 'Hello!';
		}

		if ($type instanceof Foo) {
			$this->message($message);
		}
	}

	public function message(string $message): void {}
}
