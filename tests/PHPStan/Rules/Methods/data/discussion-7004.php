<?php declare(strict_types = 1);

namespace Discussion7004;

use function PHPStan\testing\assertType;

class Foo
{
	/**
	 * @param array<array{newsletterName: string, subscriberCount: int}> $data
	 */
	public static function fromArray1(array $data): void
	{
		assertType('array<array{newsletterName: string, subscriberCount: int}>', $data);
	}

	/**
	 * @param array{array{newsletterName: string, subscriberCount: int}} $data
	 */
	public static function fromArray2(array $data): void
	{
		assertType('array{array{newsletterName: string, subscriberCount: int}}', $data);
	}

	/**
	 * @param array{newsletterName: string, subscriberCount: int} $data
	 */
	public static function fromArray3(array $data): void
	{
		assertType('array{newsletterName: string, subscriberCount: int}', $data);
	}
}

class Bar
{
	/**
	 * @param mixed $data
	 */
	public function doSomething($data): void
	{
		if (!is_array($data)) {
			return;
		}

		assertType('array', $data);
		Foo::fromArray1($data);
		Foo::fromArray2($data);
		Foo::fromArray3($data);
	}
}
