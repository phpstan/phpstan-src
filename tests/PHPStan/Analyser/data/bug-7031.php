<?php declare(strict_types = 1);

namespace Bug7031;

class SomeKey {}

function () {
	assertType('Closure(int): Generator<int, Bug7031\SomeKey, mixed, void>', static fn(int $value): iterable => yield new SomeKey);
	assertType('Closure(int): Generator<int, Bug7031\SomeKey, mixed, void>', static function (int $value): iterable { yield new SomeKey; });
};
