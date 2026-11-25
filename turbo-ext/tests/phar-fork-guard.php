<?php declare(strict_types=1);

/**
 * Differential test for Runtime::enablePharForkGuard().
 *
 * libphar serves every phar:// read as a seek-then-read pair on one shared
 * per-archive fd; after pcntl_fork() all processes share that fd's kernel
 * cursor, so concurrent reads race and return bytes from wrong offsets.
 * The guard privatizes the cursor per forked child via pthread_atfork.
 *
 * Three passes over the same workload (parent + 8 forked children hammering
 * randomized verified reads):
 *   1. control, guard not armed — must corrupt (proves the workload races)
 *   2. guard armed — must be error-free everywhere
 *   3. guard armed without /dev/fd (as on AWS Lambda), in a separate process
 *      in a user and mount namespace — must be error-free too; skipped where
 *      such namespaces are unavailable
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

/**
 * Pins the archive like the running phar is pinned in the real case —
 * without a live reference libphar closes the fd at refcount 0 and nothing
 * is shared — and warms reads so the shared fd is open with a buffered
 * window, as after boot.
 *
 * @param array<string, string> $manifest
 */
function pinAndWarm(string $pharPath, array $manifest): Phar
{
	$pin = new Phar($pharPath);
	foreach (array_slice(array_keys($manifest), 0, 20) as $name) {
		if (md5((string) file_get_contents('phar://' . $pharPath . '/' . $name)) !== $manifest[$name]) {
			fwrite(STDERR, "pre-fork sanity read failed\n");
			exit(1);
		}
	}

	return $pin;
}

// The guarded pass without /dev/fd, run in a mount namespace whose /dev is
// empty (see the parent below) — a fresh process, as atfork hooks stay armed.
if (($argv[1] ?? null) === '--no-dev-fd') {
	[, , $pharPath, $manifestPath] = $argv;
	if (file_exists('/dev/fd')) {
		fwrite(STDERR, "FAIL: /dev/fd is still present in the namespace\n");
		exit(1);
	}
	/** @var array<string, string> $manifest */
	$manifest = unserialize(file_get_contents($manifestPath));
	$pin = pinAndWarm($pharPath, $manifest);
	PHPStanTurbo\Runtime::enablePharForkGuard($pharPath);
	[$parentErrors, $corruptChildren] = forkAndHammer('phar://' . $pharPath . '/', $manifest, 3000);
	printf("guarded, no /dev/fd: parent errors %d, corrupt children %d/%d\n", $parentErrors, $corruptChildren, WORKERS);
	exit($parentErrors > 0 || $corruptChildren > 0 ? 1 : 0);
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

$pin = pinAndWarm($pharPath, $manifest);

[$controlParentErrors, $controlCorruptChildren] = forkAndHammer($pharUrl, $manifest, 1000);
printf("control (no guard):  parent errors %d, corrupt children %d/%d\n", $controlParentErrors, $controlCorruptChildren, WORKERS);

PHPStanTurbo\Runtime::enablePharForkGuard($pharPath);

[$guardedParentErrors, $guardedCorruptChildren] = forkAndHammer($pharUrl, $manifest, 2000);
printf("guarded:             parent errors %d, corrupt children %d/%d\n", $guardedParentErrors, $guardedCorruptChildren, WORKERS);

// Again without /dev/fd, as on AWS Lambda: re-run this script, with the same
// interpreter flags (from /proc/self/cmdline), in an unprivileged user and
// mount namespace that mounts an empty tmpfs over /dev.
$noDevFdExitCode = null;
$cmdline = PHP_OS_FAMILY === 'Linux' ? @file_get_contents('/proc/self/cmdline') : false;
if ($cmdline !== false) {
	exec('unshare --user --map-root-user --mount true 2>&1', $ignored, $probeExitCode);
}
if ($cmdline !== false && $probeExitCode === 0) {
	$phpArgs = explode("\0", rtrim($cmdline, "\0"));
	$phpArgs = array_slice($phpArgs, 1, (int) array_search($argv[0], $phpArgs, true) - 1);
	passthru(implode(' ', array_map('escapeshellarg', [
		'unshare', '--user', '--map-root-user', '--mount',
		'sh', '-c', 'mount -t tmpfs tmpfs /dev 2>/dev/null || exit 77; exec "$@"', 'sh',
		PHP_BINARY, ...$phpArgs, ...[__FILE__, '--no-dev-fd', $pharPath, $manifestPath],
	])) . ' 2>&1', $noDevFdExitCode);
}
if ($noDevFdExitCode === null || $noDevFdExitCode === 77) {
	echo "guarded, no /dev/fd: SKIP (needs Linux with unprivileged user namespaces)\n";
	$noDevFdExitCode = 0;
}

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
if ($noDevFdExitCode !== 0) {
	fwrite(STDERR, "FAIL: the guard did not run without /dev/fd\n");
	exit(1);
}

echo "ALL OK\n";
