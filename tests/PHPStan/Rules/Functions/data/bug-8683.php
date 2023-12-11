<?php

namespace Bug8683;

/**
 * @template T
 *
 * @param callable(): T $func
 *
 * @return T
 */
function theCaller(callable $func) {
	return $func();
}

/**
 * @return array<'test1'|'test2'|'test3'|'total', int>
 */
function myCallableFunction() {
	return ['test1' => 4, 'test2' => 45, 'test3' => 3, 'total' => 52];
}

/**
 * @return array<'test1'|'test2'|'test3'|'total', int>
 */
function IwillCallTheCallable() {
	return theCaller(fn () => myCallableFunction());
}
