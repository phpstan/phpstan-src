<?php

namespace MissingClosureNativeReturnTypehint;

class Foo
{

	public function doFoo()
	{
		\PHPStan\Testing\assertType('void', (function () {

		})());
		\PHPStan\Testing\assertType('void', (function () {
			return;
		})());
		\PHPStan\Testing\assertType('Generator<int, 1, mixed, void>', (function (bool $bool) {
			if ($bool) {
				return;
			} else {
				yield 1;
			}
		})());
		\PHPStan\Testing\assertType('1|null', (function (bool $bool) {
			if ($bool) {
				return;
			} else {
				return 1;
			}
		})());
		\PHPStan\Testing\assertType('1', (function (): int {
			return 1;
		})());
		\PHPStan\Testing\assertType('1|null', (function (bool $bool) {
			if ($bool) {
				return null;
			} else {
				return 1;
			}
		})());
		\PHPStan\Testing\assertType('1', (function (bool $bool) {
			if ($bool) {
				return 1;
			}
		})());

		\PHPStan\Testing\assertType('array{foo: \'bar\'}', (function () {
			$array = [
				'foo' => 'bar',
			];

			return $array;
		})());
	}

}
