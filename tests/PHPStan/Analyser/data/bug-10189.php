<?php

namespace Bug10189;

use function PHPStan\Testing\assertType;

interface SomeInterface {
}

/**
 * @return array|SomeInterface|null|false
 */
function _file_save_upload_from_form() {
	return [];
}

function file_managed_file_save_upload(): array {
	if (!$files = _file_save_upload_from_form()) {
		return [];
	}
	assertType('non-empty-array|Bug10189\SomeInterface', $files);
	$files = array_filter($files);
	assertType("array<mixed~0|0.0|''|'0'|array{}|false|null>", $files);

	return empty($files) ? [] : [1,2];
}
