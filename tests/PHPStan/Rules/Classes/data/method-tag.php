<?php

namespace MethodTag;

/**
 * @method intt doFoo()
 * @method int doBar(int&string $a)
 * @method int doBaz(int $a = ClassWithConstant::FOO)
 * @method int doBaz2(int $a = ClassWithConstant::BAR)
 * @method array doMissingIterablueValue()
 */
class Foo
{

}

class ClassWithConstant
{

	public const FOO = 1;

}

/**
 * @template T of int
 * @template U
 */
class Generic
{

}

/**
 * @method \Exception<int> doA()
 * @method Generic<int> doB()
 * @method Generic<int, string, float> doC()
 * @method Generic<string, string> doD()
 */
class TestGenerics
{

}

/**
 * @method Generic doA()
 */
class MissingGenerics
{

}

/**
 * @method Generic<int, array> doA()
 */
class MissingIterableValue
{

}

/**
 * @method Generic<int, callable> doA()
 */
class MissingCallableSignature
{

}

/**
 * @method Nonexistent doA()
 * @method \PropertyTagTrait\Foo doB()
 * @method fOO doC()
 */
class NonexistentClasses
{

}
