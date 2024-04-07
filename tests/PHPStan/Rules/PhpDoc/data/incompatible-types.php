<?php

namespace InvalidPhpDoc;

/**
 * @param string $unknown
 * @param int $a
 * @param array $b
 * @param array $c
 * @param int|float $d
 */
function paramTest(int $a, string $b, iterable $c, int $d)
{

}


/**
 * @param int ...$numbers invalid according to PhpStorm, but actually valid
 */
function variadicNumbers(int ...$numbers)
{

}


/**
 * @param string[] ...$strings valid according to PhpStorm, but actually invalid (accepted for now)
 */
function variadicStrings(string ...$strings)
{

}


/**
 * @return int
 */
function testReturnIntOk(): int
{

}


/**
 * @return bool
 */
function testReturnBoolOk(): bool
{

}


/**
 * @return true
 */
function testReturnTrueOk(): bool
{

}


/**
 * @return string
 */
function testReturnIntInvalid(): int
{

}


/**
 * @return string|int
 */
function testReturnIntNotSubType(): int
{

}

/**
 * @param string[] ...$strings
 */
function anotherVariadicStrings(string ...$strings)
{

}

/**
 * @param int[] $strings
 */
function incompatibleVariadicStrings(string ...$strings)
{

}

/**
 * @param string ...$numbers
 */
function incompatibleVariadicNumbers(int ...$numbers)
{

}

/**
 * @param string[] ...$strings
 */
function variadicStringArrays(array ...$strings)
{

}

/**
 * @param  array<int, int, int> $arr
 * @param  array<int, int, int> $arrX
 * @return bool <strong>true</strong> or <strong>false</strong>
 */
function unresolvableTypes(array $arr): bool
{

}

/**
 * @param Foo&Bar $foo
 * @return Foo&Bar
 */
function neverTypes($foo)
{

}

/**
 * @template T
 * @template U of \DateTimeInterface
 *
 * @param T $a
 * @param U $b
 * @param U $c
 *
 * @return U
 */
function genericWithTypeHints($a, $b, \DateTimeInterface $c): \DateTimeInterface
{
}

/**
 * @template T
 * @template U of \DateTimeInterface
 *
 * @param T $a
 * @param U $b
 *
 * @return U
 */
function genericWithTypeHintsNotSubType(int $a, \DateTime $b): \DateTime
{
}

/**
 * @template T of \DateTime
 * @param T $a
 * @return T
 */
function genericWithTypeHintsSupertype(\DateTimeInterface $a): \DateTimeInterface
{
}

/**
 * @param never $foo
 * @return never
 */
function explicitNever($foo)
{
	throw new \Exception();
}

/**
 * @param \InvalidPhpDocDefinitions\Foo<\stdClass> $foo
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, \InvalidArgumentException> $bar
 * @param \InvalidPhpDocDefinitions\FooGeneric<int> $baz
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, \InvalidArgumentException, string> $lorem
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, \Throwable> $ipsum
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, \stdClass> $dolor
 * @return \InvalidPhpDocDefinitions\Foo<\stdClass>
 */
function generics($foo, $bar, $baz, $lorem, $ipsum, $dolor)
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<int, \InvalidArgumentException>
 */
function genericsBar()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<int>
 */
function genericsBaz()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<int, \InvalidArgumentException, string>
 */
function genericsLorem()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<int, \Throwable>
 */
function genericsIpsum()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<int, \stdClass>
 */
function genericsDolor()
{

}

/**
 * @template T
 * @template U of \Exception
 * @template V of \Throwable
 * @template W of \InvalidArgumentException
 * @template X of \stdClass
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, T> $t
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, U> $u
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, V> $v
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, W> $w
 * @param \InvalidPhpDocDefinitions\FooGeneric<int, X> $x
 */
function genericGenerics($t, $u, $v, $w, $x)
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<\InvalidPhpDocDefinitions\FooGeneric<int, \stdClass>, \Exception>
 */
function genericNestedWrongTemplateArgs()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<\InvalidPhpDocDefinitions\FooGeneric<int, \Exception>, \Exception>
 */
function genericNestedOkTemplateArgs()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<\InvalidPhpDocDefinitions\FooGeneric<int>, \Exception>
 */
function genericNestedWrongArgCount()
{

}

/**
 * @return \InvalidPhpDocDefinitions\FooGeneric<\InvalidPhpDocDefinitions\Foo<int, \Exception>, \Exception>
 */
function genericNestedNonTemplateArgs()
{

}

/**
 * @template TFoo
 * @param TFoo $i
 */
function genericWrongBound(int $i)
{

}

/**
 * @param \InvalidPhpDocDefinitions\FooCovariantGeneric<int> $foo
 * @return \InvalidPhpDocDefinitions\FooCovariantGeneric<int>
 */
function genericCompatibleInvariantType($foo)
{

}

/**
 * @param \InvalidPhpDocDefinitions\FooCovariantGeneric<covariant int> $foo
 * @return \InvalidPhpDocDefinitions\FooCovariantGeneric<covariant int>
 */
function genericRedundantTypeProjection($foo)
{

}

/**
 * @param \InvalidPhpDocDefinitions\FooCovariantGeneric<*> $foo
 * @return \InvalidPhpDocDefinitions\FooCovariantGeneric<*>
 */
function genericCompatibleStarProjection($foo)
{

}

/**
 * @param \InvalidPhpDocDefinitions\FooCovariantGeneric<contravariant int> $foo
 * @return \InvalidPhpDocDefinitions\FooCovariantGeneric<contravariant int>
 */
function genericIncompatibleTypeProjection($foo)
{

}

/**
 * @param-immediately-invoked-callable $cb
 */
function callableParameterWithoutParamTag(callable $cb): void
{

}

/**
 * @param-immediately-invoked-callable $a
 * @param-later-invoked-callable $b
 */
function paramInvokedCallableWithUnknownParameter(): void
{

}

final class NotCallable
{

}

/**
 * @param-immediately-invoked-callable $a
 */
function paramInvokedCallableWithNotCallable(NotCallable $a): void
{

}

/**
 * @param-closure-this int $cb
 */
function paramClosureThisWithNonObject(callable $cb): void
{

}

/**
 * @param pure-callable(): void $cb
 * @param pure-Closure(): void $cl
 */
function pureCallableCannotReturnVoid(callable $cb, \Closure $cl): void
{

}
