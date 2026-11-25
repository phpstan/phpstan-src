<?php // lint >= 8.0

namespace Bug12978;

/**
 * @template T
 */
class Collection
{
    /**
     * @param T[] $items
     */
    public function __construct(
        private array $items = [],
    ) {}

    /**
	 * @param T $item
	 */
    public function add($item): void
    {
        $this->items[] = $item;
    }

    /**
	 * @return ?T
	 */
    public function get(int $index)
    {
        return $this->items[$index] ?? null;
    }
}

class Animal {}

class Dog extends Animal {}

/** @var Collection<Animal> */
$collectionWithExplicitType = new Collection([new Animal()]);
\PHPStan\dumpType($collectionWithExplicitType);
$collectionWithExplicitType->add(new Dog());

$collectionInferredFromConstructor = new Collection([new Animal()]);
\PHPStan\dumpType($collectionInferredFromConstructor);
$collectionInferredFromConstructor->add(new Dog());
