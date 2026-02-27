<?php declare(strict_types = 1);

namespace Bug9592Nsrt;

use function PHPStan\Testing\assertType;

class rex_response {}

class HelloWorld
{
	public function sayHello(): void
	{
		if (!method_exists('Bug9592Nsrt\rex_response', 'getNonce')) {
			return;
		}
		// The ClassConstFetch expression gets narrowed via the fix
		assertType("'Bug9592Nsrt\\\\rex_response'&hasMethod(getNonce)", rex_response::class);
	}
}
