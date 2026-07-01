<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11281Methods;

class Foo
{

	public function takesInt(int $i): void
	{
	}

	/**
	 * @param array<string, mixed> $values
	 */
	public function ternary(array $values): void
	{
		// The ternary's resulting type normalizes to mixed (mixed|string),
		// but the else branch is definitely a string passed to an int parameter.
		$this->takesInt(array_key_exists('key', $values) ? $values['key'] : ' a string');
	}

	/**
	 * @param array<string, mixed> $values
	 */
	public function coalesce(array $values): void
	{
		$this->takesInt($values['key'] ?? ' a string');
	}

}
