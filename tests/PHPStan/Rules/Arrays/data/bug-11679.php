<?php

namespace Bug11679;

use function PHPStan\Testing\assertType;

class WorkingExample
{
	/** @var array{foo?: bool} */
	private array $arr = [];

	public function sayHello(): bool
	{
		assertType('array{foo?: bool}', $this->arr);
		if (!isset($this->arr['foo'])) {
			$this->arr['foo'] = true;
			assertType('array{foo: true}', $this->arr);
		}
		assertType('array{foo: bool}', $this->arr);
		return $this->arr['foo']; // PHPStan realizes optional 'foo' is set
	}
}

class NonworkingExample
{
	/** @var array<int, array{foo?: bool}> */
	private array $arr = [];

	public function sayHello(int $index): bool
	{
		assertType('array<int, array{foo?: bool}>', $this->arr);
		if (!isset($this->arr[$index]['foo'])) {
			$this->arr[$index]['foo'] = true;
			assertType('non-empty-array<int, array{foo: true}>', $this->arr);
		}
		assertType('array<int, array{foo?: bool}>', $this->arr);
		return $this->arr[$index]['foo']; // PHPStan does not realize 'foo' is set
	}
}
