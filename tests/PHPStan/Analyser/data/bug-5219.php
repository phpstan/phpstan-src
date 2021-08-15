<?php

namespace Bug5219;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	protected function foo(string $message): void
	{
		$header = sprintf('%s-%s', '', implode('-', ['x']));

		assertType('non-empty-string', $header);
		assertType('array<non-empty-string, string>&nonEmpty', [$header => $message]);
	}

	protected function bar(string $message): void
	{
		$header = sprintf('%s-%s', '', '');

		assertType('\'-\'', $header);
		assertType('array(\'-\' => string)', [$header => $message]);
	}
}
