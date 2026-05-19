<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14647Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template TValue
 */
class Collection
{
	/** @param array<TValue> $items */
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

	public function testTypes(): void
	{
		assertType('Bug14647Nsrt\Collection<Bug14647Nsrt\Value>', $this->collect());
		assertType('Bug14647Nsrt\Collection<Bug14647Nsrt\Value>', $this->childCollect());
		assertType('static(Bug14647Nsrt\Value)', new static());
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

final class FinalFoo
{
	/** @return Box<static> */
	public function boxed(): Box
	{
		return new Box(new static());
	}

	/** @return Box<static> */
	public static function staticBoxed(): Box
	{
		return new Box(new static());
	}
}

function testFinalFoo(FinalFoo $foo): void
{
	assertType('Bug14647Nsrt\Box<Bug14647Nsrt\FinalFoo>', $foo->boxed());
	assertType('Bug14647Nsrt\Box<Bug14647Nsrt\FinalFoo>', FinalFoo::staticBoxed());
}
