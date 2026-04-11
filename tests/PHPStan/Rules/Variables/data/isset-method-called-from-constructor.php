<?php

declare(strict_types = 1);

namespace IssetMethodCalledFromConstructor;

final class MethodCalledFromConstructor {
	private int $bar;

	public function __construct(int $bar)
	{
		$this->setBar($bar);
	}

	private function setBar(int $bar): void
	{
		if (isset($this->bar)) { // $bar has no default, could be uninitialized when called from constructor - no error
			throw new \Exception('bar is set');
		}
		$this->bar = $bar;
	}
}

final class MethodCalledFromConstructorWithDefault {
	private int $bar = 1;

	public function __construct(int $bar)
	{
		$this->setBar($bar);
	}

	private function setBar(int $bar): void
	{
		if (isset($this->bar)) { // $bar has default value, always initialized - should error
			throw new \Exception('bar is set');
		}
		$this->bar = $bar;
	}
}

final class MethodNotCalledFromConstructor {
	private int $bar;

	public function __construct(int $bar)
	{
		$this->bar = $bar;
	}

	private function checkBar(): void
	{
		if (isset($this->bar)) { // Not called from constructor, property is initialized after construction - should error
			echo 'bar is set';
		}
	}
}

final class MultipleProperties {
	private int $foo;
	private int $bar = 5;

	public function __construct(int $bar)
	{
		$this->init($bar);
		$this->foo = 42;
	}

	private function init(int $bar): void
	{
		if (isset($this->foo)) { // $foo has no default, could be uninitialized - no error
			throw new \Exception('foo is set');
		}
		if (isset($this->bar)) { // $bar has default value, always initialized - should error
			echo 'bar is set';
		}
		$this->bar = $bar;
	}
}
