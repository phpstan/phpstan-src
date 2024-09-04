<?php

namespace Bug6333;

class HelloWorld
{
	/**
	 * @var array<string, array{\stdClass, int}>
	 */
	public array $detectedCheat = [];

	public function test(): void
	{
		$this->detectedCheat["playerName"][1]++;
	}
}
