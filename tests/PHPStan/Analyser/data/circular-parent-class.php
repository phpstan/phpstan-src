<?php declare(strict_types = 1);

namespace CircularParentClass;

class Foo extends Bar
{
}

class Bar extends Foo
{
}
