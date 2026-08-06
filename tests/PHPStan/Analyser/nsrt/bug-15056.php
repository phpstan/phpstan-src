<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15056;

use function PHPStan\Testing\assertType;

class ServiceWithSleep
{
	private readonly string $readonlyString;
	private string $string;

	public function __construct()
	{
		$this->readonlyString = 'foo';
		$this->string = 'bar';
	}

	public function doFoo(): void
	{
		assertType('string', $this->readonlyString);
		assertType('string|null', $this->readonlyString ?? null);
		assertType('string|null', $this->string ?? null);
	}

	/** @return list<string> */
	public function __sleep(): array
	{
		return [];
	}
}

class ServiceWithoutSleep
{
	private readonly string $readonlyString;
	private string $string;

	public function __construct()
	{
		$this->readonlyString = 'foo';
		$this->string = 'bar';
	}

	public function doFoo(): void
	{
		assertType("'foo'", $this->readonlyString);
		assertType("'foo'", $this->readonlyString ?? null);
		assertType('string|null', $this->string ?? null);
	}
}
