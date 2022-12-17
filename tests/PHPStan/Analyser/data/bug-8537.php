<?php

namespace Bug8537;

/**
 * @property int $x
 */
interface SampleInterface
{
}

class Sample implements SampleInterface
{
	/** @param array<string,mixed> $data */
	public function __construct(private array $data = [])
	{
	}

	public function __set(string $key, mixed $value): void
	{
		$this->data[$key] = $value;
	}

	public function __get(string $key): mixed
	{
		return $this->data[$key] ?? null;
	}

	public function __isset(string $key): bool
	{
		return array_key_exists($key, $this->data);
	}
}

function (): void {
	$test = new Sample();
	$test->x = 3;
	echo $test->x;
};
