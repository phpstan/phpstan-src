<?php // lint >= 8.0

namespace Bug15331;

/**
 * @template-covariant T
 */
final class Box
{
	/**
	 * @template U
	 * @param U $value
	 * @return self<U>
	 */
	public static function of(mixed $value): self
	{
		return new self();
	}

	/**
	 * @template U
	 * @param self<U> $a
	 * @param self<U> $b
	 * @return self<T|U>
	 */
	public function zip(self $a, self $b): self
	{
		return new self();
	}

	public function get(): void
	{
	}
}

function test(): void
{
	Box::of(1)->zip(Box::of(2), Box::of(3))->get(); // 💥
}
