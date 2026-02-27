<?php declare(strict_types = 1);

namespace Bug5051;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function test(?object $data): void
	{
		if ($data === null) {
			$foo = 'bar';
			$update = false;
		} else {
			$foo = 'baz';
			$update = true;
		}

		assertType('object|null', $data);
		assertType("'bar'|'baz'", $foo);

		if ($update) {
			assertType('object', $data);
		}
	}

	public function testWithBooleans(?object $data): void
	{
		if ($data === null) {
			$update = false;
			$foo = false;
		} else {
			$update = true;
			$foo = true;
		}

		if ($update) {
			assertType('object', $data);
		}
	}

	public function testWithDifferentVariableNames(?object $data): void
	{
		if ($data === null) {
			$update = false;
			$foo = 'bar';
		} else {
			$update = true;
			$fuu = 'baz';
		}

		if ($update) {
			assertType('object', $data);
		}
	}

	public function testWithoutExtraAssignment(?object $data): void
	{
		if ($data === null) {
			$update = false;
		} else {
			$update = true;
		}

		if ($update) {
			assertType('object', $data);
		}
	}
}
