<?php declare(strict_types = 1);

namespace Bug9475;

use Stringable;

final class Methods
{

	public function testMethods(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo 'Hello, ' . $this->{$this}();
		echo 'Hello, ' . $this->$this();
		echo 'Hello, ' . $this->$object();
		echo 'Hello, ' . $this->$array();

		echo 'Hello, ' . $this->$name(); // valid
		echo 'Hello, ' . $this->$stringable(); // valid
	}

	public function testStaticMethods(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo 'Hello, ' . self::{$this}();
		echo 'Hello, ' . self::$this();
		echo 'Hello, ' . self::$object();
		echo 'Hello, ' . self::$array();

		echo 'Hello, ' . self::$name(); // valid
		echo 'Hello, ' . self::$stringable(); // valid
	}

}
