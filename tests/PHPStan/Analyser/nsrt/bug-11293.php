<?php // lint >= 7.4

namespace Bug11293;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(string $s): void
	{
		if (preg_match('/data-(\d{6})\.json$/', $s, $matches) > 0) {
			assertType('array{non-falsy-string, non-falsy-string&numeric-string}', $matches);
		}
	}

	public function sayHello2(string $s): void
	{
		if (preg_match('/data-(\d{6})\.json$/', $s, $matches) === 1) {
			assertType('array{non-falsy-string, non-falsy-string&numeric-string}', $matches);
		}
	}

	public function sayHello3(string $s): void
	{
		if (preg_match('/data-(\d{6})\.json$/', $s, $matches) >= 1) {
			assertType('array{non-falsy-string, non-falsy-string&numeric-string}', $matches);
		}
	}

	public function sayHello4(string $s): void
	{
		if (preg_match('/data-(\d{6})\.json$/', $s, $matches) <= 0) {
			assertType('list{0?: string, 1?: non-falsy-string&numeric-string}', $matches);

			return;
		}

		assertType('array{non-falsy-string, non-falsy-string&numeric-string}', $matches);
	}

	public function sayHello5(string $s): void
	{
		if (preg_match('/data-(\d{6})\.json$/', $s, $matches) < 1) {
			assertType('list{0?: string, 1?: non-falsy-string&numeric-string}', $matches);

			return;
		}

		assertType('array{non-falsy-string, non-falsy-string&numeric-string}', $matches);
	}

	public function sayHello6(string $s): void
	{
		if (1 > preg_match('/data-(\d{6})\.json$/', $s, $matches)) {
			assertType('list{0?: string, 1?: non-falsy-string&numeric-string}', $matches);

			return;
		}

		assertType('array{non-falsy-string, non-falsy-string&numeric-string}', $matches);
	}
}
