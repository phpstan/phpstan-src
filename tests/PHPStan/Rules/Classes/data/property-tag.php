<?php

namespace PropertyTag;

/**
 * @property intt $a
 *
 * @property intt $b
 * @property-read intt $b
 *
 * @property intt $c
 * @property-read stringg $c
 *
 * @property-write intt $d
 * @property-read intt $d
 *
 * @property-write intt $e
 * @property-read stringg $e
 *
 * @property-read intt $f
 * @property-write stringg $g
 */
class Foo
{

}

/**
 * @property string&int $unresolvable
 */
class Bar
{

}

/**
 * @template T of int
 * @template U
 */
class Generic
{

}

/**
 * @property \Exception<int> $a
 * @property Generic<int> $b
 * @property Generic<int, string, float> $c
 * @property Generic<string, string> $d
 */
class TestGenerics
{

}

/**
 * @property Generic $a
 */
class MissingGenerics
{

}

/**
 * @property Generic<int, array> $a
 */
class MissingIterableValue
{

}

/**
 * @property Generic<int, callable> $a
 */
class MissingCallableSignature
{

}

/**
 * @property Nonexistent $a
 * @property \PropertyTagTrait\Foo $b
 * @property fOO $c
 */
class NonexistentClasses
{

}


// todo nonexistent class
// todo trait
// todo class name case
