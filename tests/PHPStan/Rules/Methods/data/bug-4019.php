<?php declare(strict_types = 1);

namespace Bug4019;

/**
 * @phpstan-template T
 */
final class Collection
{
	/**
     * @phpstan-param array<int,T> $elements
     */
    public function __construct(array $elements = [])
    {
        var_dump($elements); // We'd actually store this, but I wanted to keep this example short :)
    }
}

interface FooInterface {}

final class ConcreteFoo implements FooInterface
{
    /**
	 * @return Collection<FooInterface>
	 */
	public function getCollection(): Collection
	{
		return new Collection([$this]);
	}
}