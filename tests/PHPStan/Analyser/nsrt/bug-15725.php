<?php declare(strict_types = 1);

namespace Bug15725;

use function PHPStan\Testing\assertType;

/** @param array<string, mixed>|false $input */
function arrayOrFalse($input): void
{
	if ($input['type'] == 2) {
		assertType("non-empty-array<string, mixed>&hasOffsetValue('type', mixed~null)", $input);
		echo 'match';
	}
}

/** @param array<string, mixed> $input */
function arrayOffset(array $input): void
{
	if ($input['type'] == 'success') {
		assertType("non-empty-array<string, mixed>&hasOffsetValue('type', mixed~null)", $input);
		echo 'match';
	}
}

/** @param array<string, mixed> $input */
function nullCanEqual(array $input): void
{
	// null == 0, so the offset might still be missing
	if ($input['type'] == 0) {
		assertType('array<string, mixed>', $input);
	}
}
