<?php

namespace Bug6055;

class HelloWorld
{
	public function sayHello(): void
	{
		$this->check(null);

		$this->check(array_merge(
			['key1' => true],
			['key2' => 'value']
		));
	}

	/**
	 * @param ?array<mixed> $items
	 */
	private function check(?array $items): void
	{
	}
}
