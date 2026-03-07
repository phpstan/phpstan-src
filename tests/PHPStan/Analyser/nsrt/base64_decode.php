<?php declare(strict_types = 1);

namespace Base64Decode;

use function PHPStan\Testing\assertType;

class Foo
{

	public function nonStrictMode(string $string): void
	{
		assertType('string', base64_decode($string));
		assertType('string', base64_decode($string, false));
		assertType('string', base64_decode($string, 0));
		assertType('string', base64_decode('UEhQU3Rhbg==', false));
		assertType('string', base64_decode('!!!', false));
	}

	public function strictMode(string $string): void
	{
		assertType('string|false', base64_decode($string, true));
		assertType('string|false', base64_decode($string, 1));
		assertType('string', base64_decode(mt_rand() ? 'UEhQU3Rhbg==' : 'cm9ja3Mh', true));
		assertType('string|false', base64_decode(mt_rand() ? 'UEhQU3Rhbg==' : '!!!', true));
		assertType('false', base64_decode(mt_rand() ? '!' : '!!!', true));
	}

	public function mixedMode(string $string): void
	{
		assertType('(string|false)', base64_decode($string, unknown()));
		assertType('(string|false)', base64_decode($string, mt_rand(0, 1) === 1));
		assertType('(string|false)', base64_decode($string, mt_rand(0, 1)));
		assertType('string', base64_decode('UEhQU3Rhbg==', mt_rand(0, 1) === 1));
		assertType('string|false', base64_decode('!!!', mt_rand(0, 1) === 1));
	}

}
