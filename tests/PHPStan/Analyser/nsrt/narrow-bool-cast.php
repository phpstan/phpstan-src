<?php

namespace NarrowBoolCast;

use function PHPStan\Testing\assertType;

/** @param array<mixed> $arr */
function doFoo(string $x, array $arr): void {
	if ((bool) strlen($x)) {
		assertType('string', $x); // could be non-empty-string
	} else {
		assertType('string', $x);
	}
	assertType('string', $x);

	if ((bool) array_search($x, $arr, true)) {
		assertType('non-empty-array', $arr);
	} else {
		assertType('array', $arr);
	}
	assertType('string', $x);

	if ((bool) preg_match('~.*~', $x, $matches)) {
		assertType('array{string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string}', $matches);
}


interface Reader {
	public function getFilePath(): string|false;
}

function bug7685(Reader $reader): void {
	$filePath = $reader->getFilePath();
	if (false !== (bool) $filePath) {
		assertType('non-falsy-string', $filePath);
	}
}

function bug6006() {
	/** @var array<string, null|string> $data */
	$data = [
		'name' => 'John',
		'dob' => null,
	];

	$data = array_filter($data, fn(?string $input): bool => (bool)$input);

	assertType('', $data);
}
