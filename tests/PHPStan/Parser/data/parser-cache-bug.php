<?php

namespace ParserCacheBug;

use Attribute;

class ParserCacheBug {
	#[MyAttribute('hello')]
	protected string $foo;
	#[MyAttribute('hello')]
	protected string $bar;
}

#[Attribute]
class MyAttribute
{
	public string $arg;

	public function __construct(string $event)
	{
		$this->arg = $event;
	}
}
