<?php declare(strict_types = 1);

namespace Bug9908;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function test(): void
	{
		$a = [];
		if (rand() % 2) {
			$a = ['bar' => 'string'];
		}

		if (isset($a['bar'])) {
			$a['bar'] = 1;
		}

		assertType("array{}|array{bar: 1}", $a);
	}

	/**
	 * @param array{bar?: int} $foo
	 */
	public function sayHello(array $foo): void
	{
		echo 'Hello' . print_r($foo, true);
	}

	public function test2(): void
	{
		$a = [];
		if (rand() % 2) {
			$a = ['bar' => 'string'];
		}

		if (isset($a['bar'])) {
			$a['bar'] = 1;
		}

		$this->sayHello($a);
	}

	/**
	 * @param array{a: string, b: string}|array{a: string|null, c: string}|array{a?: string, d: string} $a
	 */
	public function moreTests($a): void
	{
		if (isset($a['a'])) {
			assertType("array{a: string, b: string}|array{a: string, c: string}|array{a: string, d: string}", $a);
		} else {
			// Could be "array{a: null, c: string}|array{d: string}"
			assertType("array{a: string|null, c: string}|array{a?: string, d: string}", $a);
		}
	}
}
