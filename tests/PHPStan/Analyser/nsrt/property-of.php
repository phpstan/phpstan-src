<?php

namespace PropertyOfType;

use function PHPStan\Testing\assertType;

class Foo
{

    public int $age;
    public string $name;

    /**
     * @param property-of<self> $property
     */
    public static function fromObject(string $property): void
    {
        assertType("'age'|'name'", $property);
    }

    /**
     * @param property-of<static> $property
     */
    public static function fromStatic(string $property): void
    {
        assertType("'age'|'name'", $property);
    }


    /**
     * @param property-of<Foo> $property
     */
    public static function fromClass(string $property): void
    {
        assertType("'age'|'name'", $property);
    }

}
