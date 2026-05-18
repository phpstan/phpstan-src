<?php

namespace BuiltinGenericAncestorStubs;

use Dom\NodeList;
use MultipleIterator;
use SplFileObject;
use WeakMap;
use function PHPStan\Testing\assertType;

function testSplFileObject(SplFileObject $file): void {
	assertType('int', $file->key());
	assertType('array|string|false', $file->current());
}

function testMultipleIterator(MultipleIterator $it): void {
	assertType('array', $it->key());
	assertType('array', $it->current());
}

/** @param WeakMap<object, string> $map */
function testWeakMap(WeakMap $map): void {
	assertType('int<0, max>', $map->count());
}

function testDomNodeList(NodeList $list): void {
	assertType('int<0, max>', $list->count());
}
