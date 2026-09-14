<?php declare(strict_types = 1);

namespace PHPStanTurboTests;

/**
 * The natively typed members TypehintHelper's differential in
 * type-family.php reflects (through the container's reflection provider,
 * which hands out the BetterReflection adapters the helper names); the
 * bodies are never run.
 */
final class TypehintFixture implements \Countable
{

	public function returnsInt(): int
	{
		return 0;
	}

	public function returnsNullableString(): ?string
	{
		return null;
	}

	public function returnsIntOrString(): int|string
	{
		return 0;
	}

	public function returnsIntOrStringOrNull(): int|string|null
	{
		return null;
	}

	public function returnsIntOrFalse(): int|false
	{
		return false;
	}

	public function returnsIntersection(): \Countable&\Traversable
	{
		return new \ArrayIterator([]);
	}

	public function returnsDnf(): (\Countable&\Traversable)|null
	{
		return null;
	}

	public function returnsSelf(): self
	{
		return $this;
	}

	public function returnsStatic(): static
	{
		return $this;
	}

	public function returnsMixed(): mixed
	{
		return null;
	}

	public function returnsIterable(): iterable
	{
		return [];
	}

	public function returnsNullableIterable(): ?iterable
	{
		return null;
	}

	public function returnsArray(): array
	{
		return [];
	}

	public function returnsCallable(): callable
	{
		return 'strlen';
	}

	public function returnsVoid(): void
	{
	}

	public function returnsNever(): never
	{
		exit();
	}

	public function returnsObject(): \Exception
	{
		return new \Exception();
	}

	public function returnsNullableObject(): ?\Exception
	{
		return null;
	}

	public function returnsBool(): bool
	{
		return true;
	}

	public function returnsUntyped()
	{
		return null;
	}

	public function takesVariadic(int ...$ints): void
	{
	}

	public function takesNullableArray(?array $values): void
	{
	}

	public function count(): int
	{
		return 0;
	}

}
