<?php

namespace TemplateDefault;

use function PHPStan\Testing\assertType;

/**
 * @template T1 = true
 * @template T2 = true
 */
class Test
{
}

/**
 * @param Test<false> $one
 * @param Test<false, false> $two
 * @param Test<false, false, false> $three
 */
function foo(Test $one, Test $two, Test $three)
{
	assertType('TemplateDefault\\Test<false, true>', $one);
	assertType('TemplateDefault\\Test<false, false>', $two);
	assertType('TemplateDefault\\Test<false, false, false>', $three);
}


/**
 * @template S = false
 * @template T = false
 */
class Builder
{
    /**
     * @phpstan-self-out self<true, T>
     */
    public function one(): void
    {
    }

    /**
     * @phpstan-self-out self<S, true>
     */
    public function two(): void
    {
    }

    /**
     * @return ($this is self<true, true> ? void : never)
     */
    public function execute(): void
    {
    }
}

function () {
	$qb = new Builder();
	assertType('TemplateDefault\\Builder<false, false>', $qb);
	$qb->one();
	assertType('TemplateDefault\\Builder<true, false>', $qb);
	$qb->two();
	assertType('TemplateDefault\\Builder<true, true>', $qb);
	assertType('void', $qb->execute());
};

function () {
	$qb = new Builder();
	assertType('TemplateDefault\\Builder<false, false>', $qb);
	$qb->two();
	assertType('TemplateDefault\\Builder<false, true>', $qb);
	$qb->one();
	assertType('TemplateDefault\\Builder<true, true>', $qb);
	assertType('void', $qb->execute());
};

function () {
	$qb = new Builder();
	assertType('TemplateDefault\\Builder<false, false>', $qb);
	$qb->one();
	assertType('TemplateDefault\\Builder<true, false>', $qb);
	assertType('*NEVER*', $qb->execute());
};
