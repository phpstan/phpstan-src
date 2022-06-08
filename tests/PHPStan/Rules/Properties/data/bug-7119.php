<?php // lint >= 8.1
declare(strict_types=1);

namespace Bug7119;

final class FooBar
{
	private readonly mixed $value;

	/**
	 * @param array{value: mixed} $data
	 */
	public function __construct(array $data)
	{
		//$this->value = $data['value']; // This triggers no PHPStan error.
		['value' => $this->value] = $data; // This triggers PHPStan error.
	}

	public function getValue(): mixed
	{
		return $this->value;
	}
}
