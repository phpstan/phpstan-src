<?php

namespace FunctionCallStatementNoSideEffectsErrorThrows;

/**
 * @phpstan-pure
 * @throws \TypeError
 */
function pureAndThrowsError(): string { return 'aaa'; }

/**
 * @phpstan-pure
 * @throws \Exception
 */
function pureAndThrowsException(): string { return 'aaa'; }

class Foo
{

	public function doFoo(string $format, string $haystack, string $needle, int $offset, int $a, int $b, string $json): void
	{
		sprintf($format, $haystack);
		strpos($haystack, $needle, $offset);
		intdiv($a, $b);
		array_combine([$haystack], [$haystack, $needle]);
		json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		pureAndThrowsError();
		pureAndThrowsException();
	}

}
