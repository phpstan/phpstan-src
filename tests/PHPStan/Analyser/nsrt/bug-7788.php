<?php

namespace Bug7788;

use function PHPStan\Testing\assertType;

/**
 * @template T of array<string, mixed>
 */
final class Props
{
    /**
     * @param T $props
     */
    public function __construct(private array $props = [])
    {
    }

	/**
	 * @template K of key-of<T>
	 * @template TDefault
	 * @param K $propKey
	 * @param TDefault $default
	 * @return T[K]|TDefault
	 */
	public function getProp(string $propKey, mixed $default = null): mixed
    {
        return $this->props[$propKey] ?? $default;
    }
}

function () {
	assertType('int', (new Props(['title' => 'test', 'value' => 30]))->getProp('value', 0));
};
