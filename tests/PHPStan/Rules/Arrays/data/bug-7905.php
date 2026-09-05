<?php declare(strict_types = 1);

namespace Bug7905Rule;

class HelloWorld
{
	/**
     * @param array<string, string> $data
	 */
	public function sayHello(array|null $data): void
	{
		$key = $data === null ? null : array_key_first($data);
		echo $key === null ? null : $data[$key];
	}
}
