<?php

namespace StaticWithThisChained;

use function PHPStan\Testing\assertType;

class Foo
{
    /** @var $this */
    public $propThis;

    /** @var static */
    public $propStatic;

    /** @var static */
    public static $propStaticStatic;

    /**
     * @return $this
     */
    public function getThis()
    {
        return $this;
    }

    /**
     * @return static
     */
    public function newStatic()
    {
        return new static();
    }

    /**
     * @return static
     */
    public static function newStaticStatic()
    {
        return new static();
    }

    public function testMethodChained(): void
    {
        assertType('$this(StaticWithThisChained\Foo)', $this->getThis());
        assertType('static(StaticWithThisChained\Foo)', $this->newStatic());
        assertType('static(StaticWithThisChained\Foo)', static::newStaticStatic());
        assertType('StaticWithThisChained\Foo', self::newStaticStatic());
        assertType('StaticWithThisChained\Foo', Foo::newStaticStatic());

        assertType('$this(StaticWithThisChained\Foo)', $this->getThis()->getThis());
        assertType('static(StaticWithThisChained\Foo)', $this->newStatic()->getThis());
        assertType('static(StaticWithThisChained\Foo)', static::newStaticStatic()->getThis());
        assertType('StaticWithThisChained\Foo', self::newStaticStatic()->getThis());
        assertType('StaticWithThisChained\Foo', Foo::newStaticStatic()->getThis());
    }

    public function testPropertyChained(): void
    {
        assertType('$this(StaticWithThisChained\Foo)', $this->propThis);
        assertType('static(StaticWithThisChained\Foo)', $this->propStatic);
        assertType('static(StaticWithThisChained\Foo)', static::$propStaticStatic);
        assertType('StaticWithThisChained\Foo', self::$propStaticStatic);
        assertType('StaticWithThisChained\Foo', Foo::$propStaticStatic);

        assertType('$this(StaticWithThisChained\Foo)', $this->propThis->propThis);
        assertType('static(StaticWithThisChained\Foo)', $this->propStatic->propThis);
        assertType('static(StaticWithThisChained\Foo)', static::$propStaticStatic->propThis);
        assertType('StaticWithThisChained\Foo', self::$propStaticStatic->propThis);
        assertType('StaticWithThisChained\Foo', Foo::$propStaticStatic->propThis);
    }
}
