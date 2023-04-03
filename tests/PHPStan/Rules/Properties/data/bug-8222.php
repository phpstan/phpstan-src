<?php // lint >= 7.4

namespace Bug8222;

class ValueCollection
{
	/** @var array<positive-int|0, string> */
	public array $values;

	public function addValue(string $value): void
	{
		$this->values[] = $value;
	}
}
