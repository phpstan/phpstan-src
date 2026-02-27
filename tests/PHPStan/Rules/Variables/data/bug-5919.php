<?php declare(strict_types = 1);

namespace Bug5919;

/**
 * @return mixed[]
 */
function queryApi(): array
{
	return [5];
}

function testWhileTrueWithTryCatch(): void
{
	while (true) {
		try {
			$s = queryApi();
			break;
		} catch (\Exception $e) {
			if (rand(0, 1)) {
				throw $e;
			}
		}
	}

	var_dump(count($s));
}

function testDoWhileWithTryCatch(): void
{
	do {
		try {
			$s = queryApi();
			break;
		} catch (\Exception $e) {
			if (rand(0, 1)) {
				throw $e;
			}
		}
	} while (true);

	var_dump(count($s));
}

function testForEverWithTryCatch(): void
{
	for (;;) {
		try {
			$s = queryApi();
			break;
		} catch (\Exception $e) {
			if (rand(0, 1)) {
				throw $e;
			}
		}
	}

	var_dump(count($s));
}
