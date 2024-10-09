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

/**
 * @template T = string
 */
class FooGenericWithDefault
{

}

/**
 * @template T
 * @template U = string
 */
class FooGenericWithSomeDefaults
{

}
