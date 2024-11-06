<?php // lint >= 8.0

namespace Bug11857Builder;

class Foo
{

	/**
	 * @param array<string, mixed> $attributes
	 * @return $this
	 */
	public function filter(array $attributes): static
	{
		return $this;
	}

	/**
	 * @param array<string, mixed> $attributes
	 * @return $this
	 */
	public function filterUsingRequest(array $attributes): static
	{
		return $this->filter($attributes);
	}

}

final class FinalFoo
{

	/**
	 * @param array<string, mixed> $attributes
	 * @return $this
	 */
	public function filter(array $attributes): static
	{
		return $this;
	}

	/**
	 * @param array<string, mixed> $attributes
	 * @return $this
	 */
	public function filterUsingRequest(array $attributes): static
	{
		return $this->filter($attributes);
	}

}
