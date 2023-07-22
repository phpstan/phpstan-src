<?php

namespace MixinRule;

/**
 * @mixin \Exception
 */
class Foo
{

}

/**
 * @mixin int
 */
class Bar
{

}

/**
 * @mixin \SplFileInfo&\Exception
 */
class Baz
{

}

/**
 * @mixin \Exception<Foo>
 * @mixin \Traversable<int, int, int>
 * @mixin \ReflectionClass<string>
 */
class Lorem
{

}

trait FooTrait
{

}

/**
 * @mixin \ReflectionClass
 * @mixin \Iterator
 * @mixin UnknownestClass
 * @mixin FooTrait
 */
class Ipsum
{
}

/**
 * @template T
 * @mixin T
 * @mixin U
 */
class Dolor
{

}

/**
 * @template T
 * @template U
 */
class Consecteur
{

}

/**
 * @mixin Consecteur<Foo>
 */
class Sit
{

}

/**
 * @mixin foo
 */
class Amet
{

}

/**
 * @mixin int
 */
interface InterfaceWithMixin
{

}

/**
 * @template-covariant T
 */
class Adipiscing
{

}

/**
 * @mixin Adipiscing<contravariant Foo>
 */
class Elit
{

}

/**
 * @mixin Adipiscing<covariant Foo>
 */
class Elit2
{

}
