<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

/**
 * Measures one benchmark from tests/bench/data and writes its metrics in the format of
 * tests/bench/expected.neon. Needs the CodSpeed CLI on PATH and CodSpeed's instrument-hooks library
 * in the INSTRUMENT_HOOKS_LIBRARY environment variable, see .github/workflows/benchmarks.yml.
 *
 * Usage: php tests/bench/run.php <benchmark file> <output file>
 */

use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use PHPStan\Process\Restart\OpcacheSettings;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use function basename;
use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function getenv;
use function getmypid;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function realpath;
use function rmdir;
use function sprintf;
use function str_ends_with;
use function sys_get_temp_dir;
use function unlink;
use const PHP_BINARY;
use const STDERR;

require_once __DIR__ . '/../../vendor/autoload.php';

// The slowest benchmark takes a few seconds when PHPStan runs natively, so a benchmark
// stuck in an infinite loop fails in the native run, before the much slower simulation
const NATIVE_RUN_SECONDS_LIMIT = 30;

// The slowest benchmark takes about 13 minutes under CodSpeed's CPU simulation. A benchmark
// that regresses far beyond that fails as not measured instead of holding up its shard.
const SIMULATION_SECONDS_LIMIT = 25 * 60;

if (!isset($argv) || count($argv) !== 3) {
	fwrite(STDERR, "Usage: php tests/bench/run.php <benchmark file> <output file>\n");
	exit(2);
}

[, $benchmarkFile, $outputFile] = $argv;
$benchmark = basename($benchmarkFile);
$benchmarkFile = realpath($benchmarkFile);
if ($benchmarkFile === false) {
	fwrite(STDERR, sprintf("Benchmark %s does not exist.\n", $argv[1]));
	exit(2);
}

// Each benchmark gets its own tmpDir, so what earlier benchmarks left in the cache does not change its numbers
$tmpDir = dirname(__DIR__, 2) . '/tmp/bench/' . $benchmark;
FileSystem::delete($tmpDir);
FileSystem::createDir($tmpDir);
$configFile = $tmpDir . '/phpstan.neon';
FileSystem::write($configFile, Neon::encode([
	'includes' => [__DIR__ . '/phpstan.neon'],
	'parameters' => ['tmpDir' => $tmpDir . '/cache'],
], true));

// measure.php does not restart PHP like bin/phpstan, so it gets the OPcache settings the restart
// would add. Valgrind also breaks PHP's detection of the stack size, so the stack limit check is
// turned off.
$command = [PHP_BINARY, '-d', 'ffi.enable=1', '-d', 'memory_limit=-1', '-d', 'zend.max_allowed_stack_size=-1'];
foreach (OpcacheSettings::getIniEntries() as $iniEntry) {
	$command[] = '-d';
	$command[] = $iniEntry;
}
$peakMemoryFile = $tmpDir . '/peak-memory';
$command = [...$command, 'tests/bench/measure.php', $configFile, $benchmarkFile, $peakMemoryFile];

$hooksLibrary = getenv('INSTRUMENT_HOOKS_LIBRARY');
if ($hooksLibrary === false || !is_file($hooksLibrary)) {
	fwrite(STDERR, "Set INSTRUMENT_HOOKS_LIBRARY to the path of CodSpeed's instrument-hooks library.\n");
	exit(2);
}

// Compiles the DI container and fills the caches in tmpDir, so the measured run reads them
$warmUp = new Process($command, __DIR__ . '/../..', ['INSTRUMENT_HOOKS_LIBRARY' => false]);
$warmUp->setTimeout(NATIVE_RUN_SECONDS_LIMIT);
try {
	$warmUp->run();
} catch (ProcessTimedOutException) {
	fwrite(STDERR, sprintf("PHPStan did not finish in %d seconds.\n", NATIVE_RUN_SECONDS_LIMIT));
	exit(1);
}
if (!$warmUp->isSuccessful()) {
	fwrite(STDERR, $warmUp->getOutput() . $warmUp->getErrorOutput());
	fwrite(STDERR, sprintf("PHPStan failed with exit code %s.\n", $warmUp->getExitCode()));
	exit(1);
}

$profileDir = sys_get_temp_dir() . '/phpstan-benchmark-' . getmypid();
if (!is_dir($profileDir) && !mkdir($profileDir, 0777, true)) {
	fwrite(STDERR, sprintf("Cannot create %s.\n", $profileDir));
	exit(1);
}

$codspeed = new Process([
	'codspeed',
	'run',
	'--mode=simulation',
	// The comparison against expected.neon happens in compare.php
	'--skip-upload',
	'--profile-folder=' . $profileDir,
	'--',
	...$command,
], __DIR__ . '/../..', ['INSTRUMENT_HOOKS_LIBRARY' => $hooksLibrary], timeout: SIMULATION_SECONDS_LIMIT);
try {
	$codspeed->run();
} catch (ProcessTimedOutException) {
	fwrite(STDERR, sprintf("CodSpeed did not finish in %d minutes.\n", SIMULATION_SECONDS_LIMIT / 60));
	exit(1);
}

// Every process gets its own file, only the one of measure.php has the measured part
$metrics = [];
foreach (glob($profileDir . '/*') ?: [] as $profileFile) {
	if (str_ends_with($profileFile, '.out')) {
		$profile = BenchmarkProfileParser::parseCallgrindOutput((string) file_get_contents($profileFile), 'tests/bench/data/' . $benchmark);
		if ($profile !== null) {
			$metrics[] = $profile;
		}
	}
	unlink($profileFile);
}
rmdir($profileDir);

$peakMemory = @file_get_contents($peakMemoryFile);
if (!$codspeed->isSuccessful() || count($metrics) !== 1 || $peakMemory === false) {
	fwrite(STDERR, $codspeed->getOutput() . $codspeed->getErrorOutput());
	fwrite(STDERR, sprintf("CodSpeed exited with code %s and measured %d benchmarks instead of one.\n", $codspeed->getExitCode(), count($metrics)));
	exit(1);
}

$result = [$benchmark => $metrics[0] + ['peakMemory' => (int) $peakMemory]];
if (file_put_contents($outputFile, Neon::encode($result, true)) === false) {
	fwrite(STDERR, sprintf("Cannot write %s.\n", $outputFile));
	exit(1);
}

echo Neon::encode($result, true);
