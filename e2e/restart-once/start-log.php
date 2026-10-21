<?php declare(strict_types = 1);

// Loaded through auto_prepend_file in php.ini. Writes one line for every
// start of the main "analyse" process, so the test can count the restarts.
if (in_array('analyse', $_SERVER['argv'] ?? [], true)) {
	file_put_contents(__DIR__ . '/starts.log', sprintf(
		"opcache=%d pcov=%d xdebug=%d\n",
		(int) ini_get('opcache.enable_cli'),
		(int) (extension_loaded('pcov') && ini_get('pcov.enabled') === '1'),
		(int) extension_loaded('xdebug'),
	), FILE_APPEND);
}
