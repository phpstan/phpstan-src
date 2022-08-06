<?php

namespace Bug7417;

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
	unset($extensions['theme']['test_basetheme']);
	unset($extensions['theme']['test_subsubtheme']);
	unset($extensions['theme']['test_subtheme']);
}


