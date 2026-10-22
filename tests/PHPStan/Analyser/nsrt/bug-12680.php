<?php declare(strict_types = 1);

namespace Bug12680;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{

	/** @return static<array-key, mixed> */
	public function pluck(): static
	{
		return new static(); // @phpstan-ignore new.static
	}

	/** @return self<array-key, mixed> */
	public function pluckSelf(): static // @phpstan-ignore return.type
	{
		return new static(); // @phpstan-ignore new.static
	}

	/** @return TValue */
	public function first() // @phpstan-ignore missingType.return
	{
	}

}

function (Collection $c): void {
	assertType('mixed', $c->pluck()->first());
	assertType('mixed', $c->pluckSelf()->first());
	assertType('mixed', (new Collection())->pluck()->first());
	assertType('mixed', (new Collection())->pluckSelf()->first());
};
