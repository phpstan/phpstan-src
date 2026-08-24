<?php declare(strict_types=1);

/**
 * Differential test for Runtime::enablePharForkGuard().
 *
 * libphar serves every phar:// read as a seek-then-read pair on one shared
 * per-archive fd; after pcntl_fork() all processes share that fd's kernel
 * cursor, so concurrent reads race and return bytes from wrong offsets.
 * The guard privatizes the cursor per forked child via pthread_atfork.
 *
 * Two passes over the same workload (parent + 8 forked children hammering
 * randomized verified reads):
 *   1. control, guard not armed — must corrupt (proves the workload races)
 *   2. guard armed — must be error-free everywhere
 *
 * The control pass runs first because pthread_atfork registrations cannot
 * be removed. Run: php -d extension=$PWD/phpstan_turbo.so tests/phar-fork-guard.php
 */

const WORKERS = 8;
const ROUNDS = 12;
const ENTRIES = 200;

if (in_array('--build', $argv, true)) {
	[, , $pharPath, $manifestPath] = $argv;
	$phar = new Phar($pharPath);
	$phar->startBuffering();
	$manifest = [];
	mt_srand(42);
	for ($i = 0; $i < ENTRIES; $i++) {
		$name = sprintf('files/entry-%03d.txt', $i);
		// sizes up to ~40KB so many entries span several 8KB stream-buffer refills
		$size = 1024 + ($i * 199) % (40 * 1024);
		$content = '';
		while (strlen($content) < $size) {
			$content .= md5((string) mt_rand(), true);
		}
		$manifest[$name] = md5(substr($content, 0, $size));
		$phar->addFromString($name, substr($content, 0, $size));
	}
	$phar->setStub('<?php __HALT_COMPILER();');
	$phar->stopBuffering();
	file_put_contents($manifestPath, serialize($manifest));
	exit(0);
}

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "phpstan_turbo extension is not loaded\n");
	exit(1);
}
if (!function_exists('pcntl_fork')) {
	echo "SKIP: pcntl is not available\n";
	exit(0);
}

/** @param array<string, string> $manifest */
function hammer(string $pharUrl, array $manifest, int $seed): int
{
	$errors = 0;
	mt_srand($seed);
	$names = array_keys($manifest);
	for ($round = 0; $round < ROUNDS; $round++) {
		shuffle($names);
		foreach ($names as $name) {
			$content = @file_get_contents($pharUrl . $name);
			if ($content === false || md5($content) !== $manifest[$name]) {
				$errors++;
			}
		}
	}
	return $errors;
}

/**
 * @param array<string, string> $manifest
 * @return array{int, int} parent errors, corrupt children
 */
function forkAndHammer(string $pharUrl, array $manifest, int $seedBase): array
{
	$children = [];
	for ($i = 0; $i < WORKERS; $i++) {
		$pid = pcntl_fork();
		if ($pid === -1) {
			fwrite(STDERR, "fork failed\n");
			exit(1);
		}
		if ($pid === 0) {
			exit(hammer($pharUrl, $manifest, $seedBase + $i) > 0 ? 1 : 0);
		}
		$children[] = $pid;
	}

	$parentErrors = hammer($pharUrl, $manifest, $seedBase + 999);

	$corruptChildren = 0;
	foreach ($children as $pid) {
		pcntl_waitpid($pid, $status);
		if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
			$corruptChildren++;
		}
	}

	return [$parentErrors, $corruptChildren];
}

$pharPath = tempnam(sys_get_temp_dir(), 'ptpfg') . '.phar';
$manifestPath = $pharPath . '.manifest';

exec(implode(' ', array_map('escapeshellarg', [
	PHP_BINARY, '-d', 'phar.readonly=0', __FILE__, '--build', $pharPath, $manifestPath,
])), $output, $exitCode);
if ($exitCode !== 0) {
	fwrite(STDERR, "building the test phar failed\n");
	exit(1);
}

/** @var array<string, string> $manifest */
$manifest = unserialize(file_get_contents($manifestPath));
$pharUrl = 'phar://' . $pharPath . '/';

// Pin the archive like the running phar is pinned in the real case — without
// a live reference libphar closes the fd at refcount 0 and nothing is shared.
$pin = new Phar($pharPath);

// warm reads so the shared fd is open with a buffered window, as after boot
foreach (array_slice(array_keys($manifest), 0, 20) as $name) {
	if (md5((string) file_get_contents($pharUrl . $name)) !== $manifest[$name]) {
		fwrite(STDERR, "pre-fork sanity read failed\n");
		exit(1);
	}
}

[$controlParentErrors, $controlCorruptChildren] = forkAndHammer($pharUrl, $manifest, 1000);
printf("control (no guard):  parent errors %d, corrupt children %d/%d\n", $controlParentErrors, $controlCorruptChildren, WORKERS);

PHPStanTurbo\Runtime::enablePharForkGuard($pharPath);

[$guardedParentErrors, $guardedCorruptChildren] = forkAndHammer($pharUrl, $manifest, 2000);
printf("guarded:             parent errors %d, corrupt children %d/%d\n", $guardedParentErrors, $guardedCorruptChildren, WORKERS);

unset($pin);
@unlink($pharPath);
@unlink($manifestPath);

if ($controlParentErrors === 0 && $controlCorruptChildren === 0) {
	fwrite(STDERR, "FAIL: the control pass did not corrupt — the workload no longer exercises the race\n");
	exit(1);
}
if ($guardedParentErrors > 0 || $guardedCorruptChildren > 0) {
	fwrite(STDERR, "FAIL: corruption with the guard armed\n");
	exit(1);
}

echo "ALL OK\n";
