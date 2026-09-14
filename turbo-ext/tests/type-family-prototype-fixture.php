<?php declare(strict_types = 1);

namespace PHPStanTurboTests;

/**
 * The members the unresolved prototype reflections' differential in
 * type-family.php transforms (reflected through the container's
 * reflection provider); the bodies are never run.
 *
 * @template T
 */
class PrototypeFixture
{

	/** @var static|null */
	public ?self $sibling = null;

	/** @var T */
	public mixed $value = null;

	/** @return static */
	public function returnsStatic(): static
	{
		return $this;
	}

	/** @return $this */
	public function returnsThis(): static
	{
		return $this;
	}

	/**
	 * @param static $other
	 * @param static|null $fallback
	 * @return static|null
	 */
	public function takesStatic(self $other, ?self $fallback = null): ?static
	{
		return $fallback ?? $other;
	}

	/**
	 * @template U
	 * @param U $value
	 * @phpstan-self-out static<U>
	 * @return $this
	 */
	public function withValue(mixed $value): static
	{
		return $this;
	}

	/**
	 * @phpstan-assert static $subject
	 * @phpstan-assert-if-true static $other
	 */
	public function assertStatic(mixed $subject, mixed $other = null): bool
	{
		return true;
	}

	/** @return T */
	public function get(): mixed
	{
		return $this->value;
	}

	/**
	 * @param callable(static): void $callback
	 * @param-closure-this static $callback
	 * @param-out static $out
	 */
	public function each(callable $callback, ?self &$out = null): void
	{
	}

	/** @throws \RuntimeException */
	public function fails(int ...$codes): static
	{
		throw new \RuntimeException((string) array_sum($codes));
	}

}

final class PrototypeSubFixture extends PrototypeFixture
{

}
