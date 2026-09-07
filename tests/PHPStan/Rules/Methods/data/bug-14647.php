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
