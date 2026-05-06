<?php declare(strict_types = 1);

namespace Bug8681Properties;

class Foo
{
	/** @var array<string, string> */
	public array $prop;

	public function test(): void
	{
		/** @var array $a */
		$a = [];
		$this->prop = $a;
	}
}
