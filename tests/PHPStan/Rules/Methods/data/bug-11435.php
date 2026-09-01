<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11435;

/**
 * @implements \IteratorAggregate<int, array{
 *     routeName: string,
 *     optional?: true,
 * }>
 */
class Example implements \IteratorAggregate
{
    /**
     * @param list<array{
     *     routeName: string,
     *     optional?: true,
     * }> $elements
     */
    public function __construct(
        private array $elements,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->elements);
    }
}
