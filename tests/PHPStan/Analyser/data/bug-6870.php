<?php declare(strict_types = 1); // onlyif PHP_VERSION_ID >= 80000

namespace Bug6870;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function foo(?string $data): void
	{
		$data === null ? throw new \Exception() : $data;
		assertType('string', $data);
	}

	public function buz(?string $data): void
	{
		$data !== null ? $data : throw new \Exception();
		assertType('string', $data);
	}

	public function bar(?string $data): void
	{
		$data || throw new \Exception();
		assertType('non-falsy-string', $data);
	}

	public function bar2(?string $data): void
	{
		$data or throw new \Exception();
		assertType('non-falsy-string', $data);
	}

	public function baz(?string $data): void
	{
		!$data && throw new \Exception();
		assertType('non-falsy-string', $data);
	}

	public function baz2(?string $data): void
	{
		!$data and throw new \Exception();
		assertType('non-falsy-string', $data);
	}

	public function boo(?string $data): void
	{
		$data ?? throw new \Exception();
		assertType('string', $data);
	}
}
