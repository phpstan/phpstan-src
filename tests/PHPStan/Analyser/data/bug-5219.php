<?php

namespace Bug5219;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	protected function foo(string $message): void
	{
		$header = sprintf('%s-%s', '', implode('-', ['x']));

		assertType('string', $header);
		assertType('array<string, string>&nonEmpty', [$header => $message]);
	}

	protected function bar(string $message): void
	{
		$header = sprintf('%s-%s', '', '');

		assertType('\'-\'', $header);
		assertType('array(\'-\' => string)', [$header => $message]);
	}
}
