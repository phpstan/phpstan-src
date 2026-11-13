<?php declare(strict_types = 1);

namespace Bug15722Rules;

/** @param array{r: array{a: array{b: int}|null}} $p */
function nested(array $p): bool
{
	$x = $p['r']['a']['b'] ?? null;

	return isset($p['r']['a']);
}

class Foo
{
	/** @var array{a: array{b: int}|null} */
	private array $data = ['a' => null];

	public function get(): ?int
	{
		return $this->data['a']['b'] ?? null;
	}

	public function has(): bool
	{
		return isset($this->data['a']['b']);
	}
}
