<?php declare(strict_types = 1);

namespace Bug9475;

use Stringable;

final class Properties
{

	public function testProperties(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo 'Hello, ' . $this->{$this}->name;
		echo 'Hello, ' . $this->$this->name;
		echo 'Hello, ' . $this->$object;
		echo 'Hello, ' . $this->$array;

		echo 'Hello, ' . $this->$name; // valid
		echo 'Hello, ' . $this->$stringable; // valid
	}

	public function testStaticProperties(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo 'Hello, ' . self::${$this}->name;
		echo 'Hello, ' . self::$$this->name;
		echo 'Hello, ' . self::$$object;
		echo 'Hello, ' . self::$$array;

		echo 'Hello, ' . self::$$name; // valid
		echo 'Hello, ' . self::$$stringable; // valid
	}

}
