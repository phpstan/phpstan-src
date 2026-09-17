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

/**
 * The properties the property reflections' differential in type-family.php
 * reads through every getter (static, visibility, native and PHPDoc types,
 * explicit mixed, promoted and readonly, deprecated and internal tags).
 *
 * @template T
 */
class PropertyReflectionFixture
{

	public static int $counter = 0;

	/** @var list<T> */
	protected array $items = [];

	/** @var non-empty-string|null */
	private ?string $label = null;

	public $untyped;

	public mixed $explicitMixed = null;

	/** @readonly */
	public int $docReadonly = 0;

	/** @deprecated use $items */
	public int $old = 0;

	/** @internal */
	public int $internalOne = 0;

	/** @var static|null */
	public ?self $sibling = null;

	public function __construct(
		public readonly int $promoted = 0,
		/** @var positive-int */
		protected int $promotedDoc = 1,
	)
	{
	}

}

final class PropertyReflectionSubFixture extends PropertyReflectionFixture
{

}

/**
 * The methods the method reflections' differential in type-family.php reads
 * through ResolvedMethodReflection / ChangedTypeMethodReflection (static,
 * private, final, abstract, constructor, void, pure / impure, deprecated,
 * internal, by-reference, no named arguments, self-out).
 */
abstract class MethodReflectionFixture
{

	public function __construct()
	{
	}

	public static function create(): static
	{
		return new static();
	}

	private function hidden(): int
	{
		return 1;
	}

	final public function sealed(): string
	{
		return '';
	}

	abstract protected function todo(): void;

	public function nothing(): void
	{
	}

	/** @phpstan-pure */
	public function pure(): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public function impure(): int
	{
		return 1;
	}

	/** @deprecated use pure() */
	public function old(): int
	{
		return 1;
	}

	/** @internal */
	public function internalOne(): int
	{
		return 1;
	}

	public function &byReference(): array
	{
		static $a = [];
		return $a;
	}

	/** @no-named-arguments */
	public function positional(int ...$values): int
	{
		return 1;
	}

	public function fluent(): static
	{
		return $this;
	}

}
