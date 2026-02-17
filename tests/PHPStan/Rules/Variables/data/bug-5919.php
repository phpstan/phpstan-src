<?php declare(strict_types = 1);

namespace Bug5919;

/**
 * @return mixed[]
 */
function queryApi(): array
{
	return [5];
}

function testSimpleTryCatch(): void
{
	while (true) {
		try {
			$s = queryApi();
			break;
		} catch (\Exception $e) {
			if (false) {
				throw $e;
			}
		}
	}

	var_dump(count($s));
}

function isTimeout(): bool
{
	return true;
}

function testTryCatchWithRethrow(): void
{
	while (true) {
		try {
			$s = queryApi();
			break;
		} catch (\Exception $e) {
			if (isTimeout()) {
				throw $e;
			}
		}
	}

	var_dump(count($s));
}
