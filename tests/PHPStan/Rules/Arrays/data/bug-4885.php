<?php

namespace Bug4885;

class Foo
{
	/** @param array{word?: string} $data */
	public function sayHello(array $data): void
	{
		echo ($data['word'] ?? throw new \RuntimeException('bye')) . ', World!';
		echo 'Again, the word was: ' . $data['word'];
	}
}
