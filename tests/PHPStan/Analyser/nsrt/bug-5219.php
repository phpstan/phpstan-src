<?php

namespace Bug5219;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	protected function foo(string $message, string $x): void
	{
		$header = sprintf('%s-%s', '', implode('-', [$x]));

		assertType('non-falsy-string', $header);
		assertType('non-empty-array<non-falsy-string, string>', [$header => $message]);
	}

	protected function bar(string $message): void
	{
		$header = sprintf('%s-%s', '', '');

		assertType('\'-\'', $header);
		assertType('array{-: string}', [$header => $message]);
	}
}
