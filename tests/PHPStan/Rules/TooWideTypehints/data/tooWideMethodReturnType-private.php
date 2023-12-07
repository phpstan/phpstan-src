<?php

namespace TooWideMethodReturnType;

class Foo
{

	private function foo(): \Generator {
		yield 1;
		yield 2;
		return 3;
	}

	private function bar(): ?string {
		return null;
	}

	private function baz(): ?string {
		return 'foo';
	}

	private function lorem(): ?string {
		if (rand(0, 1)) {
			return '1';
		}

		return null;
	}

	public function ipsum(): ?string {
		return null;
	}

	private function dolor(): ?string {
		$f = function () {
			return null;
		};

		$c = new class () {
			public function doFoo() {
				return null;
			}
		};

		return 'str';
	}

	private function dolor2(): ?string {
		if (rand()) {
			return 'foo';
		}
	}

	/**
	 * @return string|null
	 */
	private function dolor3() {
		if (rand()) {
			return 'foo';
		}
	}

	/**
	 * @return string|int
	 */
	private function dolor4() {
		if (rand()) {
			return 'foo';
		}
	}

	/**
	 * @return string|null
	 */
	private function dolor5() {
		if (rand()) {
			return 'foo';
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	private function dolor6() {
		if (rand()) {
			return 'foo';
		}

		return 'bar';
	}

}

trait FooTrait
{

	private function doFoo(): ?int
	{
		return 1;
	}

}

class UsesFooTrait
{

	use FooTrait;

}
