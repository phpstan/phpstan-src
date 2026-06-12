<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14647;

/**
 * @template TValue
 */
class Collection
{
	/** @param  array<TValue>  $items */
	public function __construct(private readonly array $items) {}

	/** @return array<TValue> */
	public function items(): array
	{
		return $this->items;
	}
}

abstract class AbstractValue
{
	final public function __construct() {}

	/** @return Collection<static> */
	public function collect(): Collection
	{
		return new Collection([new static()]);
	}
}

final class Value extends AbstractValue
{
	/** @return Collection<static> */
	#[\Override]
	public function collect(): Collection
	{
		return parent::collect();
	}

	/** @return Collection<static> */
	public function childCollect(): Collection
	{
		return new Collection([new static()]);
	}
}

/**
 * @template T
 */
class Box
{
	/** @param T $value */
	public function __construct(private readonly mixed $value) {}

	/** @return T */
	public function get(): mixed
	{
		return $this->value;
	}
}

final class FinalWithStaticMethods
{
	/** @return Box<static> */
	public static function staticBoxed(): Box
	{
		return new Box(new static());
	}

	/** @return Box<static> */
	public function instanceBoxed(): Box
	{
		return new Box(new static());
	}

	/** @param Box<static> $box */
	public function acceptBox(Box $box): void
	{
	}

	public function test(): void
	{
		$this->acceptBox(new Box(new static()));
		$this->acceptBox(new Box(new FinalWithStaticMethods()));
	}
}
