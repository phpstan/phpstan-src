<?php

namespace Bug2689;

class HelloWorld
{
	/**
	 * @var callable[]
	 */
	private $listeners;

	public function addListener(string $name, callable $callback): void
	{
		$this->listeners[$name][] = $callback;
	}
}
