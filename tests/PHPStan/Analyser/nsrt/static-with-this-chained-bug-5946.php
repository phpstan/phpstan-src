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
        assertType('static(StaticWithThisChained\Foo)', self::newStaticStatic());
        assertType('StaticWithThisChained\Foo', Foo::newStaticStatic());

        assertType('$this(StaticWithThisChained\Foo)', $this->getThis()->getThis());
        assertType('static(StaticWithThisChained\Foo)', $this->newStatic()->getThis());
        assertType('static(StaticWithThisChained\Foo)', static::newStaticStatic()->getThis());
        assertType('static(StaticWithThisChained\Foo)', self::newStaticStatic()->getThis());
        assertType('StaticWithThisChained\Foo', Foo::newStaticStatic()->getThis());
    }

    public function testPropertyChained(): void
    {
        assertType('$this(StaticWithThisChained\Foo)', $this->propThis);
        assertType('static(StaticWithThisChained\Foo)', $this->propStatic);
        assertType('static(StaticWithThisChained\Foo)', static::$propStaticStatic);
        assertType('static(StaticWithThisChained\Foo)', self::$propStaticStatic);
        assertType('static(StaticWithThisChained\Foo)', Foo::$propStaticStatic); // should be StaticWithThisChained\Foo

        assertType('$this(StaticWithThisChained\Foo)', $this->propThis->propThis);
        assertType('static(StaticWithThisChained\Foo)', $this->propStatic->propThis);
        assertType('static(StaticWithThisChained\Foo)', static::$propStaticStatic->propThis);
        assertType('static(StaticWithThisChained\Foo)', self::$propStaticStatic->propThis);
        assertType('static(StaticWithThisChained\Foo)', Foo::$propStaticStatic->propThis); // should be StaticWithThisChained\Foo
    }
}

class Bar extends Foo
{
    public function testMethodChained(): void
    {
        assertType('$this(StaticWithThisChained\Bar)', $this->getThis());
        assertType('static(StaticWithThisChained\Bar)', $this->newStatic());
        assertType('static(StaticWithThisChained\Bar)', static::newStaticStatic());
        assertType('static(StaticWithThisChained\Bar)', self::newStaticStatic());
        assertType('StaticWithThisChained\Foo', Foo::newStaticStatic());
        assertType('StaticWithThisChained\Bar', Bar::newStaticStatic());

        assertType('$this(StaticWithThisChained\Bar)', $this->getThis()->getThis());
        assertType('static(StaticWithThisChained\Bar)', $this->newStatic()->getThis());
        assertType('static(StaticWithThisChained\Bar)', static::newStaticStatic()->getThis());
        assertType('static(StaticWithThisChained\Bar)', self::newStaticStatic()->getThis());
        assertType('StaticWithThisChained\Foo', Foo::newStaticStatic()->getThis());
        assertType('StaticWithThisChained\Bar', Bar::newStaticStatic()->getThis());
    }

    public function testPropertyChained(): void
    {
        assertType('$this(StaticWithThisChained\Bar)', $this->propThis);
        assertType('static(StaticWithThisChained\Bar)', $this->propStatic);
        assertType('static(StaticWithThisChained\Bar)', static::$propStaticStatic);
        assertType('static(StaticWithThisChained\Bar)', self::$propStaticStatic);
        assertType('static(StaticWithThisChained\Bar)', Foo::$propStaticStatic); // should be StaticWithThisChained\Foo (non-static and Foo, not Bar)
        assertType('static(StaticWithThisChained\Bar)', Bar::$propStaticStatic); // should be StaticWithThisChained\Bar (non-static)

        assertType('$this(StaticWithThisChained\Bar)', $this->propThis->propThis);
        assertType('static(StaticWithThisChained\Bar)', $this->propStatic->propThis);
        assertType('static(StaticWithThisChained\Bar)', static::$propStaticStatic->propThis);
        assertType('static(StaticWithThisChained\Bar)', self::$propStaticStatic->propThis);
        assertType('static(StaticWithThisChained\Bar)', Foo::$propStaticStatic->propThis); // should be StaticWithThisChained\Foo (non-static and Foo, not Bar)
        assertType('static(StaticWithThisChained\Bar)', Bar::$propStaticStatic->propThis); // should be StaticWithThisChained\Bar (non-static)
    }
}
