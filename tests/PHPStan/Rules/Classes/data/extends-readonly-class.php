<?php // lint >= 8.2

namespace ExtendsReadOnlyClass;

class Nonreadonly
{

}

readonly class ReadonlyClass
{

}

class Lorem extends Nonreadonly // ok
{

}

readonly class Ipsum extends ReadonlyClass // ok
{

}

readonly class Foo extends Nonreadonly // not ok
{

}

class Bar extends ReadonlyClass // not ok
{

}

new class extends ReadonlyClass { // not ok

};

new readonly class extends Nonreadonly { // not ok

};
