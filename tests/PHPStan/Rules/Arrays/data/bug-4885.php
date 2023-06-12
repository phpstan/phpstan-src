<?php // lint >= 8.0

namespace Bug4885\Arrays;

class Foo
{
	/** @param array{word?: string} $data */
	public function sayHello(array $data): void
	{
		echo ($data['word'] ?? throw new \RuntimeException('bye')) . ', World!';
		echo 'Again, the word was: ' . $data['word'];
	}
}
