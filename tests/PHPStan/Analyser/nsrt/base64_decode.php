<?php declare(strict_types = 1);

namespace Base64Decode;

use function PHPStan\Testing\assertType;

class Foo
{

	public function nonStrictMode(string $string): void
	{
		assertType('string', base64_decode($string));
		assertType('string', base64_decode($string, false));
	}

	public function strictMode(string $string): void
	{
		assertType('string|false', base64_decode($string, true));
	}

	public function constantInput(): void
	{
		assertType("'Hello world'", base64_decode('SGVsbG8gd29ybGQ='));
		assertType("'Hello world'", base64_decode('SGVsbG8gd29ybGQ=', false));
		assertType("'Hello world'", base64_decode('SGVsbG8gd29ybGQ=', true));
		assertType('false', base64_decode('not valid base64 @@@', true));
		assertType('string', base64_decode('not valid base64 @@@', false));
	}

}
