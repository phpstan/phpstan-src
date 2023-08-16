<?php declare(strict_types = 1);

namespace Bug9765;

class HelloWorld
{

	public static function runner(): \Closure
	{
		return function (int $arg) {
			return $this->add($arg);
		};
	}

	public function do(): void
	{
		$c = self::runner();
		print $c->bindTo($this)(5);
	}

	private function add(int $a): int
	{
		return $a + 1;
	}

}

class HelloWorld2
{

	/** @var int */
	private $foo;

	public static function runner(): \Closure
	{
		return function (int $arg) {
			if ($arg > 0) {
				$this->foo = $arg;
			} else {
				echo $this->foo;
			}
		};
	}

	public function do(): void
	{
		$c = self::runner();
		print $c->bindTo($this)(5);
	}

}

class HelloWorld3
{

	private const FOO = 1;

	public static function runner(): \Closure
	{
		return function (int $arg) {
			echo $this::FOO;
		};
	}

	public function do(): void
	{
		$c = self::runner();
		print $c->bindTo($this)(5);
	}

}
