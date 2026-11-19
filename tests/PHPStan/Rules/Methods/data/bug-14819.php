<?php

namespace Bug14819;

use Closure;

interface Entry {}

class ConcreteEntry implements Entry {}

/**
 * @template TEntry of Entry
 */
class Result {}

/**
 * @extends Result<ConcreteEntry>
 */
class ConcreteResult extends Result {}

/**
 * @template TEntry of Entry
 */
class Pool
{
    /** @var Result<TEntry> */
    private Result $result;

    /** @param Result<TEntry> $result */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /** @return Result<TEntry> */
    public function lookup(): Result
    {
        return $this->result;
    }
}

/**
 * @extends Pool<ConcreteEntry>
 */
class ConcretePool extends Pool
{
    public function __construct()
    {
        parent::__construct(new ConcreteResult());
    }
}

class Tester
{
    /**
     * @param Closure(Pool<ConcreteEntry>):Result<ConcreteEntry> $lookup
     * @return Result<ConcreteEntry>
     */
    public function run(Closure $lookup): Result
    {
        return $lookup(new ConcretePool());
    }

    /** @return Result<ConcreteEntry> */
    public function inline(): Result
    {
        return $this->run(
            fn(Pool $pool): Result => $pool->lookup(),
        );
    }

    /** @return Result<ConcreteEntry> */
    public function assigned(): Result
    {
        $lookup = fn(Pool $pool): Result => $pool->lookup();

        return $this->run($lookup);
    }
}

