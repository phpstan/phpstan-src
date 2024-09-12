<?php

namespace StaticLateBinding;

use function PHPStan\Testing\assertType;

class A
{
    public static function retStaticConst(): int
    {
        return 1;
    }

    /**
     * @return static
     */
    public static function retStatic()
    {
        return new static();
    }

    /**
     * @return static
     */
    public function retNonStatic()
    {
        return new static();
    }

    /**
     * @param-out int $out
     */
    public static function outStaticConst(&$out): int
    {
        $out = 1;
    }
}

class B extends A
{
    /**
     * @return 2
     */
    public static function retStaticConst(): int
    {
        return 2;
    }

    /**
     * @param-out 2 $out
     */
    public static function outStaticConst(&$out): int
    {
        $out = 2;
    }

    public function foo(): void
    {
        $clUnioned = mt_rand() === 0
            ? A::class
            : X::class;

        assertType('int', A::retStaticConst());
        assertType('2', B::retStaticConst());
        assertType('2', self::retStaticConst());
        assertType('2', static::retStaticConst());
        assertType('int', parent::retStaticConst());
        assertType('2', $this->retStaticConst());
        assertType('bool', X::retStaticConst());
        assertType('*ERROR*', $clUnioned->retStaticConst()); // should be bool|int

        assertType('int', A::retStaticConst(...)());
        assertType('2', B::retStaticConst(...)());
        assertType('2', self::retStaticConst(...)());
        assertType('2', static::retStaticConst(...)());
        assertType('int', parent::retStaticConst(...)());
        assertType('2', $this->retStaticConst(...)());
        assertType('bool', X::retStaticConst(...)());
        assertType('mixed', $clUnioned->retStaticConst(...)()); // should be bool|int

        assertType('StaticLateBinding\A', A::retStatic());
        assertType('StaticLateBinding\B', B::retStatic());
        assertType('static(StaticLateBinding\B)', self::retStatic());
        assertType('static(StaticLateBinding\B)', static::retStatic());
        assertType('static(StaticLateBinding\B)', parent::retStatic());
        assertType('static(StaticLateBinding\B)', $this->retStatic());
        assertType('bool', X::retStatic());
        assertType('bool|StaticLateBinding\A|StaticLateBinding\X', $clUnioned::retStatic()); // should be bool|StaticLateBinding\A

        assertType('static(StaticLateBinding\B)', A::retNonStatic());
        assertType('static(StaticLateBinding\B)', B::retNonStatic());
        assertType('static(StaticLateBinding\B)', self::retNonStatic());
        assertType('static(StaticLateBinding\B)', static::retNonStatic());
        assertType('static(StaticLateBinding\B)', parent::retNonStatic());
        assertType('static(StaticLateBinding\B)', $this->retNonStatic());
        assertType('bool', X::retNonStatic());
        assertType('*ERROR*', $clUnioned->retNonStatic()); // should be bool|static(StaticLateBinding\B)

        A::outStaticConst($v);
        assertType('int', $v);
        B::outStaticConst($v);
        assertType('2', $v);
        self::outStaticConst($v);
        assertType('2', $v);
        static::outStaticConst($v);
        assertType('2', $v);
        parent::outStaticConst($v);
        assertType('int', $v);
        $this->outStaticConst($v);
        assertType('2', $v);
        X::outStaticConst($v);
        assertType('bool', $v);
        $clUnioned->outStaticConst($v);
        assertType('bool', $v); // should be bool|int

        assertType('StaticLateBinding\A', A::retStatic(...)());
        assertType('StaticLateBinding\B', B::retStatic(...)());
        assertType('Closure', self::retStatic()(...)); // should be static(StaticLateBinding\B)
        assertType('static(StaticLateBinding\B)', static::retStatic(...)());
        assertType('static(StaticLateBinding\B)', parent::retStatic(...)());
        assertType('static(StaticLateBinding\B)', $this->retStatic(...)());
        assertType('bool', X::retStatic(...)());
        assertType('mixed', $clUnioned::retStatic(...)()); // should be bool|StaticLateBinding\A
    }
}

class X
{
    public static function retStaticConst(): bool
    {
        return false;
    }

    /**
     * @param-out bool $out
     */
    public static function outStaticConst(&$out): void
    {
        $out = false;
    }

    public static function retStatic(): bool
    {
        return false;
    }

    public function retNonStatic(): bool
    {
        return false;
    }
}
