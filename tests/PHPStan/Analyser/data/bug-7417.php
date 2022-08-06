<?php

namespace Bug7417;

use function PHPStan\Testing\assertType;

/**
 * Reads configuration data from the storage.
 * @return array|bool
 */
function readThing()
{
	return ['module' => ['test' => 0]];
}

function doFoo() {
	$extensions = readThing();
	$extensions['theme']['test_basetheme'] = 0;
// This is the important part of the test. Themes are ordered alphabetically
// in core.extension so this will come before it's base theme.
	$extensions['theme']['test_subtheme'] = 0;
	$extensions['theme']['test_subsubtheme'] = 0;
	assertType('non-empty-array', $extensions); // could be more precise
	unset($extensions['theme']['test_basetheme']);
	unset($extensions['theme']['test_subsubtheme']);
	unset($extensions['theme']['test_subtheme']);
	assertType("hasOffsetValue('theme', mixed)&non-empty-array", $extensions);
}


