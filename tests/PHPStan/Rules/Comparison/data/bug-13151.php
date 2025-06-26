<?php declare(strict_types = 1);

namespace Bug13151;

class HelloWorld
{
	/**
	 * @return array{a:0|1, b:0|1, c:0|1}
	 */
	public function extractAsArray(): array
	{
		return [
			'a' => 1,
			'b' => 1,
			'c' => 1
		];
	}

	public function test(): void
	{
		echo in_array(0, $this->extractAsArray(), true) ? "True" : "False";
	}

	public function test2(): void
	{
		echo in_array(0, $this->extractAsArray()) ? "True" : "False";
	}
}
