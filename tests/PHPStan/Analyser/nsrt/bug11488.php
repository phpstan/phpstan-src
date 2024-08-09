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
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) !== 1) {
			assertType('array{0: mixed, 1?: string|null}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) !== 2) {
			assertType('array{0: mixed, 1?: string|null}', $row);
		} else {
			// should be array{0: mixed, 1: string|null}
			assertType('array{mixed, mixed}', $row);
		}

		if (count($row) !== 3) {
			// should be array{0: mixed, 1?: string|null}
			assertType('array{0: mixed, 1?: mixed}', $row);
		} else {
			assertType('*NEVER*', $row);
		}
	}

	/**
	 * @param array{mixed}|array{mixed, string|null, mixed} $row
	 */
	protected function test(array $row): void
	{
		if (count($row) !== 1) {
			assertType('array{mixed, string|null, mixed}', $row);
		} else {
			assertType('array{mixed}', $row);
		}

		if (count($row) !== 2) {
			assertType('array{mixed, string|null, mixed}|array{mixed}', $row);
		} else {
			assertType('*NEVER*', $row);
		}

		if (count($row) !== 3) {
			assertType('array{mixed}', $row);
		} else {
			assertType('array{mixed, string|null, mixed}', $row);
		}
	}
}
