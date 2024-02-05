<?php // lint >= 8.1

namespace Bug6773;

final class Repository
{
	/**
	 * @param array<string, string> $data
	 */
	public function __construct(private readonly array $data)
	{
	}

	public function remove(string $key): void
	{
		unset($this->data[$key]);
	}
}
