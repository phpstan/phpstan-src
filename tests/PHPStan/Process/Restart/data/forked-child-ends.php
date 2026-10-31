<?php declare(strict_types = 1);

// The restarted process: it adopts the composed ini named in
// PHPSTAN_RESTART_INI, forks a worker that ends normally, and reports whether
// the ini survived the worker.

use PHPStan\Process\Restart\RestartManager;

require __DIR__ . '/../../../../../vendor/autoload.php';

$temporaryIni = (string) getenv('PHPSTAN_RESTART_INI');

// diagnose never restarts, so this only takes ownership of the ini
RestartManager::run(['bin/phpstan', 'diagnose'], null, null, 'phpstan');

$pid = pcntl_fork();
if ($pid === 0) {
	exit(0);
}

pcntl_waitpid($pid, $status);

echo is_file($temporaryIni) ? 'kept' : 'deleted';
