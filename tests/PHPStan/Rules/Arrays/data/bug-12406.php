<?php

namespace Bug12406;

final class HelloWorld
{
	/** @var array<string, int> */
	protected array $words = [];

	public function sayHello(string $word, int $count): void
	{
		$this->words[$word] ??= 0;
		$this->words[$word] += $count;
	}
}
