<?php

namespace Bug11488;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param array{mixed}|array{0: mixed, 1?: string|null} $row
	 */
	protected function testOptionalKeys(array $row): void
	{
		if (count($row) === 1) {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) !== 1) {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) !== 2) {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) !== 3) {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}
	}

	/**
	 * @param array{mixed}|array{mixed, string|null, mixed} $row
	 */
	protected function test(array $row): void
	{
		if (count($row) !== 1) {
			assertType('array{mixed, string|null, mixed}', $row);
		}

		if (count($row) !== 2) {
			assertType('array{mixed, string|null, mixed}|array{mixed}', $row);
		}

		if (count($row) !== 3) {
			assertType('array{mixed}', $row);
		}
	}
}
