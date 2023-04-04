<?php

namespace ClosedResourceType;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

function doFoo() {
	$fp = tmpfile();
	if ($fp === false) {
		die('could not open temp file');
	}

	assertType('resource', $fp);
	fclose($fp);
	assertType('closed-resource', $fp);
}

function union() {
	$fp = tmpfile();
	if ($fp === false) {
		die('could not open temp file');
	}

	assertType('resource', $fp);
	if (rand(0,1) === 0) {
		fclose($fp);
	}
	assertType('closed-resource|resource', $fp);
}
