<?php

namespace InvalidPhpDocDefinitions;

class Foo
{

}

/**
 * @template T
 * @template U of \Exception
 */
class FooGeneric
{

}

/**
 * @template-covariant T
 */
class FooCovariantGeneric
{

}
