<?php

namespace ParamClosureScopePhpDocRule;

class Foo
{

}

/**
 * @param-closure-scope Foo $i
 */
function validParamClosureScope(callable $i) {

}

/**
 * @param-closure-scope Foo $b
 */
function invalidParamClosureScopeParamName($a) {

}

/**
 * @param-closure-scope string $i
 */
function nonObjectParamClosureScope(callable $i) {

}

/**
 * @param-closure-scope \stdClass&\Exception $i
 */
function unresolvableParamClosureScope(callable $i) {

}

/**
 * @param-closure-scope Foo $i
 */
function paramClosureScopeAboveNonClosure(string $i) {

}

/**
 * @param-closure-scope \Exception<int, float> $i
 */
function invalidParamClosureScopeGeneric(callable $i) {

}

/**
 * @param-closure-scope FooBar<mixed> $i
 */
function invalidParamClosureScopeWrongGenericParams(callable $i) {

}

/**
 * @param-closure-scope FooBar<int> $i
 */
function invalidParamClosureScopeNotAllGenericParams(callable $i) {

}

/**
 * @template T of int
 * @template TT of string
 */
class FooBar {

}
