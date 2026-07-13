<?php declare(strict_types = 1);

/*
 * Parse-only microbench: native PHPStanTurbo\ParserRunner vs $parser->parse()
 * over all of src/, interleaved rounds, reporting user CPU + peak memory.
 */

$root = dirname(__DIR__, 2);
chdir($root);
require $root . '/vendor/autoload.php';

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src', FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
	if ($file->getExtension() === 'php') {
		$files[] = $file->getPathname();
	}
}
sort($files);
$codes = [];
$bytes = 0;
foreach ($files as $f) {
	$codes[$f] = file_get_contents($f);
	$bytes += strlen($codes[$f]);
}
printf("corpus: %d files, %.1f MB\n", count($codes), $bytes / 1048576);

$lexer = new PhpParser\Lexer();
$phpVersion = PhpParser\PhpVersion::fromString('8.5');

function userTime(): float
{
	$r = getrusage();
	return $r['ru_utime.tv_sec'] + $r['ru_utime.tv_usec'] / 1e6;
}

$rounds = 6; // interleaved: odd = native, even = php
$results = ['native' => [], 'php' => []];

for ($round = 0; $round < $rounds; $round++) {
	$mode = $round % 2 === 0 ? 'native' : 'php';
	$parser = new PhpParser\Parser\Php8($lexer, $phpVersion);
	gc_collect_cycles();
	$memBefore = memory_get_usage();
	$t0 = userTime();
	$nodes = 0;
	foreach ($codes as $code) {
		$handler = new PhpParser\ErrorHandler\Collecting();
		if ($mode === 'native') {
			$ast = PHPStanTurbo\ParserRunner::parse($parser, $code, $handler);
		} else {
			$ast = $parser->parse($code, $handler);
		}
		$nodes += count($ast);
		unset($ast);
	}
	$elapsed = userTime() - $t0;
	$results[$mode][] = $elapsed;
	printf("round %d  %-6s  %6.3fs user  transient-mem %5.1f MB  peak %5.1f MB\n",
		$round, $mode, $elapsed, (memory_get_usage() - $memBefore) / 1048576, memory_get_peak_usage() / 1048576);
}

$avg = static fn (array $a) => array_sum($a) / count($a);
// drop the first round of each mode (warmup: class resolution, table extraction)
$nat = array_slice($results['native'], 1);
$php = array_slice($results['php'], 1);
printf("\nnative mean %.3fs   php mean %.3fs   speedup %.2fx (%.1f%% less time)\n",
	$avg($nat), $avg($php), $avg($php) / $avg($nat), 100 * (1 - $avg($nat) / $avg($php)));

// retained-AST memory comparison: keep all ASTs of one mode in memory
foreach (['native', 'php'] as $mode) {
	$parser = new PhpParser\Parser\Php8($lexer, $phpVersion);
	gc_collect_cycles();
	$before = memory_get_usage();
	$asts = [];
	foreach ($codes as $f => $code) {
		$handler = new PhpParser\ErrorHandler\Collecting();
		$asts[$f] = $mode === 'native'
			? PHPStanTurbo\ParserRunner::parse($parser, $code, $handler)
			: $parser->parse($code, $handler);
	}
	gc_collect_cycles();
	printf("retained ASTs (%s): %.1f MB\n", $mode, (memory_get_usage() - $before) / 1048576);
	unset($asts);
	gc_collect_cycles();
}
