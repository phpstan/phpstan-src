<?php declare(strict_types = 1);

namespace Bug9833;

class HelloWorld
{
	public function nativeArrayReturnsNull(): array
	{
		if (rand(0, 1)) {
			return null;
		}
		return [];
	}

	/** @return array<string, int> */
	public function phpDocOnlyReturnsNull()
	{
		if (rand(0, 1)) {
			return null;
		}
		return [];
	}

	/** @return array<string, int> */
	public function nativeArrayReturnsWrongPhpDoc(): array
	{
		return ['a' => 'hello'];
	}

	public function nativeIntReturnsNull(): int
	{
		return null;
	}

	public function nativeStringReturnsNull(): string
	{
		return null;
	}
}
