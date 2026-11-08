<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15406;

use function PHPStan\Testing\assertType;

final class Node
{
    /** @param list<self> $children */
    public function __construct(public readonly array $children, public readonly int $value) {}
}

/**
 * @template T
 * @template U
 *
 * @param list<T> $list
 * @param callable(T): iterable<U> $fn
 *
 * @return list<U>
 */
function collect(array $list, callable $fn): array
{
    $result = [];
    foreach ($list as $value) {
        foreach ($fn($value) as $item) {
            $result[] = $item;
        }
    }

    return $result;
}

/**
 * @param list<Node> $nodes
 */
function twoLevels(array $nodes): void
{
    assertType('list<int>', collect($nodes, static fn (Node $node): array
        => collect($node->children, static fn (Node $child): array => [$child->value]),
    ));
}

/**
 * @param list<Node> $nodes
 */
function threeLevels(array $nodes): void
{
    assertType('list<int>', collect($nodes, static fn (Node $node): array
        => collect($node->children, static fn (Node $child): array
            => collect($child->children, static fn (Node $grandChild): array => [$grandChild->value]),
        ),
    ));
}

/**
 * @param list<Node> $nodes
 */
function threeLevelsGenerators(array $nodes): void
{
    assertType('list<int>', collect($nodes, static fn (Node $node): iterable
        => yield from collect($node->children, static fn (Node $child): iterable
            => yield from collect($child->children, static fn (Node $grandChild): iterable => yield $grandChild->value),
        ),
    ));
}
