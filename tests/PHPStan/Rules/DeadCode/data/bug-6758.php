<?php

namespace Bug6758;

class HelloWorld
{
	private const HELLO = 'Hi';

	public function speak(): string
	{
		return $this::HELLO;
	}
}
