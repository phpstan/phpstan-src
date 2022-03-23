<?php declare(strict_types = 1); // lint >= 8.0

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
		assertType('non-empty-string', $data);
	}

	public function baz(?string $data): void
	{
		!$data && throw new \Exception();
		assertType('non-empty-string', $data);
	}

	public function boo(?string $data): void
	{
		$data ?? throw new \Exception();
		assertType('string', $data);
	}
}
