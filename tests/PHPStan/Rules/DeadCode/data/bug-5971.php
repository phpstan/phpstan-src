<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug5971;

class TestEmpty
{
	private ?array $test;

	public function write(): void
	{
		$this->test = [];
	}

	public function read(): bool
	{
		return empty($this->test);
	}
}

class TestIsset
{
	private ?string $test;

	public function write(string $string): void
	{
		$this->test = $string;
	}

	public function read(): bool
	{
		return isset($this->test);
	}
}
