<?php declare(strict_types = 1);

// Loaded through auto_prepend_file in php.ini, so it runs before bin/phpstan in
// every start of the main analyse process - the one that restarts as well as
// the restarted one, which the probe in bootstrapFiles never sees. One line
// per start; check.php counts them.
if (in_array('analyse', $_SERVER['argv'] ?? [], true)) {
	file_put_contents(__DIR__ . '/starts.log', getmypid() . "\n", FILE_APPEND);
}
