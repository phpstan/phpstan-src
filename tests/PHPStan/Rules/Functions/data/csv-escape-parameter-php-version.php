<?php

namespace CsvEscapeParameterPhpVersion;

function doFoo(string $line): void
{
	if (PHP_VERSION_ID < 80400) {
		str_getcsv($line);
	}

	if (PHP_VERSION_ID >= 80400) {
		str_getcsv($line);
	}
}
