<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

/**
 * Prints the benchmarks in data/ that one shard of the Benchmarks workflow runs, one per line:
 * the benchmarks are split into shards by the instructions expected.neon has for them.
 *
 * Usage: php tests/bench/shard.php <number of shards> <shard, from 1>
 */

use Nette\Neon\Neon;
use function basename;
use function count;
use function file_get_contents;
use function fwrite;
use function glob;
use function is_file;
use function max;
use const STDERR;

require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($argv) || count($argv) !== 3 || (int) $argv[1] < 1 || (int) $argv[2] < 1 || (int) $argv[2] > (int) $argv[1]) {
	fwrite(STDERR, "Usage: php tests/bench/shard.php <number of shards> <shard, from 1>\n");
	exit(2);
}

$expectedFile = __DIR__ . '/expected.neon';
$expected = is_file($expectedFile) ? Neon::decode((string) file_get_contents($expectedFile)) : [];
$expectedInstructions = [];
foreach ($expected as $benchmark => $metrics) {
	$expectedInstructions[$benchmark] = $metrics['instructions'];
}

$costs = [];
foreach (glob(__DIR__ . '/data/*.php') ?: [] as $file) {
	$benchmark = basename($file);
	// a benchmark without expected metrics yet is usually a reproducer of something slow
	$costs[$benchmark] = $expectedInstructions[$benchmark] ?? ($expectedInstructions === [] ? 1 : max($expectedInstructions));
}

foreach (BenchmarkShards::split($costs, (int) $argv[1])[(int) $argv[2] - 1] ?? [] as $benchmark) {
	echo $benchmark . "\n";
}
