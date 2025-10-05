<?php

namespace Bug10394;

class SomeClass
{
	private int $integer = 0;

	public function __construct()
	{
		// try commenting this out!
		$this->integer = 45;

		if (is_resource($this->integer)) {
			throw new \Exception('A resource??');
		}
	}
}
