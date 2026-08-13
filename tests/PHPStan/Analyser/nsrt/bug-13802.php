<?php declare(strict_types = 1);

namespace Bug13802;

use function PHPStan\Testing\assertType;

/**
 * @return array<string, string>
 */
function createArray(): array {
	return ['arr' => 'key'];
}

function (): void {
	$arr = createArray();

	assertType('array<string, string>', $arr);

	foreach ($arr as &$val) {
		assertType('string', $val);
		$val = preg_replace('/[^\x20-\x7E]/', '', $val);
		assertType('string|null', $val);
		$val = str_replace(' ', '', $val ?? '');
		assertType('string', $val);
	}

	assertType('array<string, string>', $arr);
};
