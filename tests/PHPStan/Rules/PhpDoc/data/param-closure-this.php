<?php

namespace ParamClosureThisPhpDocRule;

class Foo
{

}

/**
 * @param-closure-this Foo $i
 */
function validParamClosureThis(callable $i) {

}

/**
 * @param-closure-this Foo $b
 */
function invalidParamClosureThisParamName($a) {

}

/**
 * @param-closure-this string $i
 */
function nonObjectParamClosureThis(callable $i) {

}

/**
 * @param-closure-this \stdClass&\Exception $i
 */
function unresolvableParamClosureThis(callable $i) {

}

/**
 * @param-closure-this Foo $i
 */
function paramClosureThisAboveNonClosure(string $i) {

}

/**
 * @param-closure-this \Exception<int, float> $i
 */
function invalidParamClosureThisGeneric(callable $i) {

}

/**
 * @param-closure-this FooBar<mixed> $i
 */
function invalidParamClosureThisWrongGenericParams(callable $i) {

}

/**
 * @param-closure-this FooBar<int> $i
 */
function invalidParamClosureThisNotAllGenericParams(callable $i) {

}

/**
 * @template T of int
 * @template TT of string
 */
class FooBar {

}
