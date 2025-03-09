<?php

namespace Bug11679;

class WorkingExample
{
	/** @var array{foo?: bool} */
	private array $arr = [];

	public function sayHello(): bool
	{
		if (!isset($this->arr['foo'])) {
			$this->arr['foo'] = true;
		}
		return $this->arr['foo'];
	}
}

class NonworkingExample
{
	/** @var array<int, array{foo?: bool}> */
	private array $arr = [];

	public function sayHello(int $index): bool
	{
		if (!isset($this->arr[$index]['foo'])) {
			$this->arr[$index]['foo'] = true;
		}
		return $this->arr[$index]['foo'];
	}
}
