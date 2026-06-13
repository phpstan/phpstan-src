<?php

declare(strict_types=1);

// Simulates a bootstrap file (e.g. larastan booting Laravel) that installs a
// global exception handler swallowing uncaught exceptions into a clean exit.
// Without PHPStan catching the ResultCacheMetaExtension exception itself, the
// throw from getHash() during result cache restore would escape to this handler
// and the process would silently exit 0 - skipping analysis with green CI.
set_exception_handler(static function (\Throwable $e): void {
	fwrite(STDERR, 'Swallowed by global exception handler: ' . $e->getMessage() . "\n");
	exit(0);
});
