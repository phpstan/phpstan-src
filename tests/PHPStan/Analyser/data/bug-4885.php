<?php

namespace Bug4885;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @param array{word?: string} $data */
	public function sayHello(array $data): void
	{
		echo ($data['word'] ?? throw new \RuntimeException('bye')) . ', World!';
		assertType('array{word: string}', $data);
	}
}
