<?php declare(strict_types = 1);

/**
 * Whole-walk differential test of the native engine classes (the expression
 * and statement handlers, NodeScopeResolver and their processors): a
 * NodeScopeResolver walk over a corpus is recorded node by node — every node
 * the callback sees, the precise PHPDoc and native type of every expression,
 * and the scope state at every statement — once in a process with the
 * shadowing classes active and once in a process where the PHP
 * implementations run; the two traces must be identical.
 *
 * Unlike the prefixed harnesses (scope-family.php, type-family.php) the two
 * sides live in two processes, so a handler is compared as the engine really
 * runs it: native classes calling native classes under their real names.
 *
 *   php -d extension=… turbo-ext/tests/walk-trace.php [--shards=N] [--keep=DIR] <path>...
 *
 * Without paths it walks the default corpus below. The children locate the
 * extension through TURBO_DLL (default: turbo-ext/phpstan_turbo.so), like
 * smoke.php's Type-family children. Exits 0 when every shard matches.
 *
 * A file the parser rejects still gets its walk (recording FILE-EX) but is
 * kept out of the analysed paths: the reflection's source locators parse the
 * analysed files when they look a class up, and a rejection there (the name
 * resolver's PhpParser\Error, which FileNodesFetcher does not catch) would
 * break the walk of every other file of the run that looks up a class. One
 * child parses the corpus up front and hands the rejected files to the others.
 */

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPStan\Turbo\TurboExtensionEnabler;
use PHPStan\Type\VerbosityLevel;

$root = dirname(__DIR__, 2);

/** @return list<string> */
function walkTraceFiles(string $root, array $paths): array
{
	$files = [];
	foreach ($paths as $path) {
		if (is_file($path)) {
			$files[] = realpath($path);
			continue;
		}
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file) {
			if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
				$files[] = $file->getRealPath();
			}
		}
	}
	sort($files);

	return array_values(array_unique($files));
}

/** @param list<string> $analysedFiles */
function walkTraceContainer(string $root, string $mode, array $analysedFiles): Container
{
	$containerFactory = new ContainerFactory($root);

	return $containerFactory->create(
		sys_get_temp_dir() . '/phpstan-turbo-walk-trace-' . $mode,
		[$containerFactory->getConfigDirectory() . '/config.level8.neon', $containerFactory->getConfigDirectory() . '/bleedingEdge.neon'],
		$analysedFiles,
	);
}

if (($argv[1] ?? '') === '--parse-check') {
	// parse check: --parse-check <out> <path>... — writes the files the
	// parser rejects, one per line, parsed the way the walk parses them
	$out = $argv[2];
	$paths = array_slice($argv, 3);
	require $root . '/vendor/autoload.php';

	$allFiles = walkTraceFiles($root, $paths);
	$container = walkTraceContainer($root, 'php', $allFiles);
	$parser = $container->getService('pathRoutingParser');
	$parser->setAnalysedFiles($allFiles);
	$rejected = [];
	foreach ($allFiles as $file) {
		try {
			$parser->parseFile($file);
		} catch (Throwable) {
			$rejected[] = $file . "\n";
		}
	}
	file_put_contents($out, implode('', $rejected));
	exit(0);
}

if (($argv[1] ?? '') === '--child') {
	// child: --child <mode> <shard> <shards> <out> <rejected-files-list> <path>...
	[, , $mode, $shard, $shards, $out, $rejectedList] = $argv;
	$paths = array_slice($argv, 7);
	require $root . '/vendor/autoload.php';
	if ($mode === 'native') {
		TurboExtensionEnabler::activateIfCompatible();
		if (!TurboExtensionEnabler::isActive()) {
			fwrite(STDERR, "walk-trace: the extension did not activate (version mismatch?)\n");
			exit(2);
		}
	}

	$allFiles = walkTraceFiles($root, $paths);
	$files = [];
	foreach ($allFiles as $i => $file) {
		if ($i % (int) $shards === (int) $shard) {
			$files[] = $file;
		}
	}

	$rejectedFiles = file($rejectedList, FILE_IGNORE_NEW_LINES);
	$container = walkTraceContainer($root, $mode, array_values(array_diff($allFiles, $rejectedFiles)));
	$fileHelper = $container->getByType(FileHelper::class);
	$resolver = $container->getByType(NodeScopeResolver::class);
	$resolver->setAnalysedFiles($allFiles);
	$container->getService('pathRoutingParser')->setAnalysedFiles($allFiles);
	$parser = $container->getService('defaultAnalysisParser');
	$scopeFactory = $container->getByType(ScopeFactory::class);
	$precise = VerbosityLevel::precise();

	$handle = fopen($out, 'w');
	foreach ($files as $file) {
		fwrite($handle, '### ' . substr($file, strlen($root) + 1) . "\n");
		$callback = static function (Node $node, Scope $scope) use ($handle, $precise): void {
			$line = get_class($node) . '@' . $node->getStartLine();
			try {
				if ($node instanceof Node\Expr) {
					$line .= ' T=' . $scope->getType($node)->describe($precise) . ' N=' . $scope->getNativeType($node)->describe($precise);
					if ($scope instanceof MutatingScope) {
						// the rest of the callback scope's ask paths (NodeCallbackScope):
						// the void-keeping read, the filtered scopes replaying their
						// conditions onto stored results, the function-call stack
						$line .= ' K=' . $scope->getKeepVoidType($node)->describe($precise);
						if ($node instanceof Node\Expr\Instanceof_ || $node instanceof Node\Expr\BinaryOp\Identical || $node instanceof Node\Expr\BooleanNot) {
							$truthyScope = $scope->filterByTruthyValue($node);
							$falseyScope = $scope->filterByFalseyValue($node);
							$line .= ' FT=' . $truthyScope->getType($node)->describe($precise)
								. ' FN=' . $falseyScope->getNativeType($node)->describe($precise)
								. ' FK=' . $truthyScope->getKeepVoidType($node)->describe($precise);
						}
						if ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\MethodCall) {
							$line .= ' P=' . $scope->pushInFunctionCall(null, null, false)->popInFunctionCall()->getType($node)->describe($precise);
						}
					}
				} elseif ($node instanceof Node\Stmt && $scope instanceof MutatingScope) {
					$line .= ' S=' . json_encode($scope->debug(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
				}
			} catch (Throwable $e) {
				$line .= ' EX=' . get_class($e) . ': ' . $e->getMessage();
			}
			fwrite($handle, $line . "\n");
		};
		try {
			$resolver->resetPerFileAnalysisState();
			$resolver->processNodes(
				$parser->parseFile($file),
				$scopeFactory->create(ScopeContext::create($fileHelper->normalizePath($file)), $callback),
				$callback,
			);
		} catch (Throwable $e) {
			fwrite($handle, 'FILE-EX ' . get_class($e) . ': ' . $e->getMessage() . "\n");
		}
	}
	fclose($handle);
	exit(0);
}

// driver
$shards = 4;
$keep = null;
$paths = [];
foreach (array_slice($argv, 1) as $arg) {
	if (str_starts_with($arg, '--shards=')) {
		$shards = max(1, (int) substr($arg, 9));
	} elseif (str_starts_with($arg, '--keep=')) {
		$keep = substr($arg, 7);
	} else {
		$paths[] = $arg;
	}
}
if ($paths === []) {
	$paths = [
		$root . '/tests/PHPStan/Analyser/nsrt',
		$root . '/src/Analyser',
		$root . '/src/Type/Constant',
		$root . '/turbo-ext/tests/walk-trace-fixtures',
	];
}
foreach ($paths as $i => $path) {
	$realPath = realpath($path);
	if ($realPath === false) {
		fwrite(STDERR, "walk-trace: $path does not exist\n");
		exit(2);
	}
	$paths[$i] = $realPath;
}

$extension = getenv('TURBO_DLL');
if (!is_string($extension) || $extension === '') {
	$extension = __DIR__ . '/../phpstan_turbo.so';
}
$dir = $keep ?? sys_get_temp_dir() . '/phpstan-turbo-walk-trace-' . getmypid();
@mkdir($dir, 0777, true);

$rejectedList = $dir . '/rejected-files.list';
$cmd = sprintf(
	'%s -d memory_limit=-1 -d extension=%s %s --parse-check %s %s',
	escapeshellarg(PHP_BINARY),
	escapeshellarg($extension),
	escapeshellarg(__FILE__),
	escapeshellarg($rejectedList),
	implode(' ', array_map('escapeshellarg', $paths)),
);
$process = proc_open($cmd, [1 => ['file', $rejectedList . '.stdout', 'w'], 2 => ['file', $rejectedList . '.stderr', 'w']], $pipes);
if ($process === false) {
	fwrite(STDERR, "proc_open failed\n");
	exit(2);
}
$exitCode = proc_close($process);
if ($exitCode !== 0) {
	fwrite(STDERR, sprintf("walk-trace parse check failed with exit code %d:\n%s\n", $exitCode, file_get_contents($rejectedList . '.stderr') . file_get_contents($rejectedList . '.stdout')));
	exit(2);
}

$processes = [];
foreach (['php', 'native'] as $mode) {
	for ($shard = 0; $shard < $shards; $shard++) {
		$out = sprintf('%s/%s-%d.trace', $dir, $mode, $shard);
		$cmd = sprintf(
			'%s -d memory_limit=-1 -d extension=%s %s --child %s %d %d %s %s %s',
			escapeshellarg(PHP_BINARY),
			escapeshellarg($extension),
			escapeshellarg(__FILE__),
			$mode,
			$shard,
			$shards,
			escapeshellarg($out),
			escapeshellarg($rejectedList),
			implode(' ', array_map('escapeshellarg', $paths)),
		);
		$process = proc_open($cmd, [1 => ['file', $out . '.stdout', 'w'], 2 => ['file', $out . '.stderr', 'w']], $pipes);
		if ($process === false) {
			fwrite(STDERR, "proc_open failed\n");
			exit(2);
		}
		$processes[] = [$mode, $shard, $out, $process];
	}
}

$failed = false;
foreach ($processes as [$mode, $shard, $out, $process]) {
	$exitCode = proc_close($process);
	if ($exitCode !== 0) {
		fwrite(STDERR, sprintf("walk-trace child %s/%d failed with exit code %d:\n%s\n", $mode, $shard, $exitCode, file_get_contents($out . '.stderr') . file_get_contents($out . '.stdout')));
		$failed = true;
	}
}
if ($failed) {
	exit(2);
}

$differences = 0;
$lines = 0;
for ($shard = 0; $shard < $shards; $shard++) {
	$php = file(sprintf('%s/php-%d.trace', $dir, $shard), FILE_IGNORE_NEW_LINES);
	$native = file(sprintf('%s/native-%d.trace', $dir, $shard), FILE_IGNORE_NEW_LINES);
	$lines += count($php);
	$file = '';
	$count = max(count($php), count($native));
	for ($i = 0; $i < $count; $i++) {
		$a = $php[$i] ?? '<missing>';
		$b = $native[$i] ?? '<missing>';
		if (str_starts_with($a, '### ')) {
			$file = substr($a, 4);
		}
		if ($a === $b) {
			continue;
		}
		$differences++;
		if ($differences <= 20) {
			printf("DIFF %s (shard %d line %d)\n  php:    %s\n  native: %s\n", $file, $shard, $i + 1, substr($a, 0, 2000), substr($b, 0, 2000));
		}
		// the rest of a diverged file is noise
		while ($i + 1 < $count && !str_starts_with($php[$i + 1] ?? '### ', '### ')) {
			$i++;
		}
	}
}

if ($keep === null) {
	array_map('unlink', glob($dir . '/*'));
	@rmdir($dir);
}

if ($differences > 0) {
	printf("walk-trace: %d diverging files (%d trace lines)\n", $differences, $lines);
	exit(1);
}
printf("walk-trace: identical (%d trace lines)\n", $lines);
exit(0);
