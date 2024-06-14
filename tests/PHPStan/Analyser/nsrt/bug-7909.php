<?php

namespace Bug7909;

use function PHPStan\Testing\assertType;

function (): void {
	$filenames = array_filter(['file1', 'file2'], 'file_exists');
	assertType("array{0?: 'file1', 1?: 'file2'}", $filenames);
};
