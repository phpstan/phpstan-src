<?php // lint >= 8.0

namespace Bug4885Types;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @param array{word?: string} $data */
	public function sayHello(array $data): void
	{
		echo ($data['word'] ?? throw new \RuntimeException('bye')) . ', World!';
		assertType('array{word: string}', $data);
	}

	/** @param array{word?: string|null} $data */
	public function sayHi(array $data): void
	{
		echo ($data['word'] ?? throw new \RuntimeException('bye')) . ', World!';
		assertType('array{word: string}', $data);
	}
}
