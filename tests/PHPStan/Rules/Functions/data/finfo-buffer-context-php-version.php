<?php

namespace FinfoBufferContextPhpVersion;

use finfo;

function doFoo(finfo $finfo, string $data): void
{
	if (PHP_VERSION_ID < 80500) {
		finfo_buffer($finfo, $data, FILEINFO_NONE, null);
	}

	if (PHP_VERSION_ID >= 80500) {
		finfo_buffer($finfo, $data, FILEINFO_NONE, null);
	}
}
