<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13440;

use Closure;

/** @template T */
interface Foo {}

/**
 * @template TVal
 * @template TReturn
 */
class Box
{
    /**
     * @param TVal $val
     * @param Closure(Foo<TVal>): TReturn $cb
     */
    public function __construct(
        private mixed $val,
        private Closure $cb,
    ) {
    }

    /**
     * @template TNewReturn
     * @param Closure(Foo<TVal>): TNewReturn $cb
     * @return self<TVal, TNewReturn>
     */
    public function test(Closure $cb): self
    {
        return new self($this->val, $cb);
    }
}
