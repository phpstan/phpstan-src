<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClassString;

class A
{
    public function __construct(public int $i)
    {
    }
}

abstract class B
{
    public function __construct(public int $i)
    {
    }
}

class C extends B
{
}

interface D
{
}

class Foo
{
    /**
     * @return class-string<A>
     */
    public static function returnClassStringA(): string
    {
        return A::class;
    }

    /**
     * @return class-string<B>
     */
    public static function returnClassStringB(): string
    {
        return B::class;
    }

    /**
     * @return class-string<C>
     */
    public static function returnClassStringC(): string
    {
        return C::class;
    }

    /**
     * @return class-string<D>
     */
    public static function returnClassStringD(): string
    {
        return D::class;
    }
}

$classString = Foo::returnClassStringA();
$error = new (Foo::returnClassStringA())('O_O');
$error = new ($classString)('O_O');
$error = new $classString('O_O');

$classString = Foo::returnClassStringB();
$ok = new (Foo::returnClassStringB())('O_O');
$ok = new ($classString)('O_O');
$ok = new $classString('O_O');

$classString = Foo::returnClassStringC();
$error = new (Foo::returnClassStringC())('O_O');
$error = new ($classString)('O_O');
$error = new $classString('O_O');

$classString = Foo::returnClassStringD();
$ok = new (Foo::returnClassStringD())('O_O');
$ok = new ($classString)('O_O');
$ok = new $classString('O_O');

$className = A::class;
$error = new ($className)('O_O');
$error = new $className('O_O');
$error = new A('O_O');
