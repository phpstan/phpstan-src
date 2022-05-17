<?php declare(strict_types = 1);

namespace Bug7248;

/**
 * @phpstan-type A array{
 *     data?: array<string, mixed>,
 *     extensions?: array<string, mixed>
 * }
 */
class HelloWorld
{
    /**
     * @phpstan-return A
     */
    public function toArray(): array {
		return [];
	}
}

function () {
	$hw = new HelloWorld;
	assert(['extensions' => ['foo' => 'bar']] === $hw->toArray());
};
