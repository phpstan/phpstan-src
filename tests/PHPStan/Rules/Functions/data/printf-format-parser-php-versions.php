<?php

namespace PrintfFormatParserPhpVersions;

function doFoo(float $f): void
{
	sprintf('%h', $f);

	if (PHP_VERSION_ID >= 80000) {
		sprintf('%h', $f);
	} else {
		sprintf('%h', $f);
	}
}
