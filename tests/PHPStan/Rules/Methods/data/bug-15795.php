<?php // lint >= 8.1

namespace Bug15795;

use RuntimeException;

interface A {}
interface B {}

/** @template T of (A&B) */
class Batch
{
    /** @param class-string<T> $type */
    public function __construct(private readonly string $type) {}

    /**
     * @param list<A&B> $items
     * @return list<string>
     */
    public function names(array $items): array
    {
        $names = [];
        foreach ($items as $item) {
            if (!$item instanceof $this->type) {
                throw new RuntimeException();
            }
            $names[] = $this->name($item);
        }
        return $names;
    }

    /** @param T $item */
    private function name(A&B $item): string
    {
        return $item::class;
    }
}
