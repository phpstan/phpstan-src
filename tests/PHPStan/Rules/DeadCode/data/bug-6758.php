<?php

namespace Bug6758;

class HelloWorld
{
	private const HELLO1 = 'Hi';
	private const HELLO2 = 'Hi';
	private const HELLO3 = 'Hi';

	public function speak1(): string
	{
		return $this::HELLO1;
	}

	public function speak2(): string
	{
		$self = $this;
		return $self::HELLO2;
	}

	public function speak3(): string
	{
		$self = new self();
		return $self::HELLO3;
	}
}
