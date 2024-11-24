<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClassString;

class A
{
    public function __construct(public int $i)
    {
    }
}

class HelloWorld
{
    /**
     * @return class-string<A>
     */
    public static function sayHelloBug(): string
    {
        return A::class;
    }
}

$classString = HelloWorld::sayHelloBug();
$bug = new (HelloWorld::sayHelloBug())('O_O');
$bug = new ($classString)('O_O');
$bug = new $classString('O_O');

$className = A::class;
$ok = new ($className)('O_O');
$ok = new $className('O_O');

$ok = new A('O_O');
