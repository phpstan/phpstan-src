<?php declare(strict_types = 1);

namespace Bug7381;

/**
 * @template T of array<string, mixed>
 */
trait AttributeTrait
{
    /**
     * @template K of key-of<T>
     * @param K $key
     * @return T[K]|null
     */
    public function getAttribute(string $key)
    {
        return $this->getAttributes()[$key] ?? null;
    }
}

/**
 * @phpstan-type Attrs array{foo?: string}
 */
class Foo {
    /** @use AttributeTrait<Attrs> */
	use AttributeTrait;

	/** @return Attrs */
	public function getAttributes(): array
	{
		return [];
	}
}

/**
 * @phpstan-type Attrs array{foo?: string, bar?: string}
 */
class Bar {
    /** @use AttributeTrait<Attrs> */
	use AttributeTrait;

	/** @return Attrs */
	public function getAttributes(): array
	{
		return [];
	}
}
