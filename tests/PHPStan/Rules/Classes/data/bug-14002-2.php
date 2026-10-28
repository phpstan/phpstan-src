<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14002Part2;

use Attribute;

/**
 * @template T
 */
class A {
	
} 

/** @template T of object */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity 
{
    /** @phpstan-param class-string<EntityRepository<T>>|null $repositoryClass */
    public function __construct(
        public readonly string|null $repositoryClass = null,
        public readonly bool $readOnly = false,
    ) {
    }
}

/**
 * @template T
 * @extends EntityRepository<A<T>>
 */
class B extends EntityRepository {
	
}

/**
 * @template T of object
 */
class EntityRepository {
	
}

$e = new Entity(B::class);
