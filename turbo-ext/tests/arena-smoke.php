<?php declare(strict_types=1);

// Multi-process differential test of PHPStanTurbo\ArenaCache: records
// published in one process must be readable, byte-for-byte identical, in
// every other process mapping the same run's arena — including after the
// name is unlinked. Racing publishers of the same key must converge, and
// everything must degrade to a miss (never an error) when the arena is gone.
//
// Run: php -d extension=.../phpstan_turbo.so arena-smoke.php  (parent mode)
// The script re-invokes itself as child processes.

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "extension not loaded\n");
	exit(2);
}

use PHPStanTurbo\ArenaCache;

$failures = 0;
function check(bool $cond, string $msg): void
{
	global $failures;
	if (!$cond) {
		$failures++;
		echo "FAIL: $msg\n";
	}
}

// Shared fixtures — both sides compare against the same literals.
function fixtures(): array
{
	return [
		'scalars' => ['int' => -42, 'float' => 1.5, 'true' => true, 'false' => false, 'null' => null, 'str' => "bin\0ary\xff"],
		'nested' => ['a' => ['b' => ['c' => [1, 2, 3]]], 'mixed-keys' => [7 => 'seven', 'x' => 'y', 0 => 'zero']],
		'empty' => [],
		'unicode' => ['klíč' => 'hodnota λ 🚀'],
	];
}

function hashFixtures(): array
{
	$rows = [];
	for ($i = 0; $i < 500; $i++) {
		$rows["fn_$i"] = ["string", "param$i" => "int|null", $i];
	}
	$rows['strlen'] = ['string', 'str' => 'string'];
	$rows[123] = ['int-keyed row'];
	return $rows;
}

function spawnChild(string $mode, string $arenaName): array
{
	// Windows CI provides the built DLL path via TURBO_DLL (see phar.yml);
	// everywhere else the .so sits next to the tests.
	$extension = getenv('TURBO_DLL');
	if (!is_string($extension) || $extension === '') {
		$extension = __DIR__ . '/../phpstan_turbo.so';
	}
	$cmd = sprintf(
		'%s -d extension=%s %s %s %s',
		escapeshellarg(PHP_BINARY),
		escapeshellarg($extension),
		escapeshellarg(__FILE__),
		escapeshellarg($mode),
		escapeshellarg($arenaName),
	);
	$process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
	if ($process === false) {
		fwrite(STDERR, "proc_open failed\n");
		exit(2);
	}
	return [$process, $pipes];
}

function waitChild(array $childHandle): array
{
	[$process, $pipes] = $childHandle;
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$exitCode = proc_close($process);
	return [$exitCode, $stdout, $stderr];
}

$mode = $argv[1] ?? 'parent';

if ($mode === 'child-read') {
	// attach, verify all parent-published records round-trip, publish own
	$name = $argv[2];
	check(ArenaCache::attach($name), 'child: attach');
	check(ArenaCache::attach($name), 'child: attach is idempotent');

	check(ArenaCache::hasRecord('fixtures'), 'child: hasRecord(fixtures)');
	check(ArenaCache::lookup('fixtures') === fixtures(), 'child: fixtures round-trip identical');
	check(ArenaCache::lookup('missing') === null, 'child: missing record is null');
	check(!ArenaCache::hasRecord('missing'), 'child: hasRecord(missing) false');
	check(!ArenaCache::hasRecord('rejected-object'), 'child: object publish left no record');

	$rows = hashFixtures();
	check(ArenaCache::lookupHash('sigmap', 'strlen') === $rows['strlen'], 'child: hash entry strlen');
	check(ArenaCache::lookupHash('sigmap', 'fn_250') === $rows['fn_250'], 'child: hash entry fn_250');
	check(ArenaCache::lookupHash('sigmap', '123') === $rows[123], 'child: int-keyed hash entry via decimal string');
	check(ArenaCache::lookupHash('sigmap', 'nope') === null, 'child: absent hash entry');
	check(ArenaCache::lookupHash('fixtures', 'strlen') === null, 'child: lookupHash on value record is null');
	check(ArenaCache::lookup('sigmap') === null, 'child: lookup on hash record is null');
	check(ArenaCache::lookupHashAll('sigmap') === $rows, 'child: lookupHashAll identical incl. order and int keys');
	check(ArenaCache::lookupHashAll('fixtures') === null, 'child: lookupHashAll on value record is null');
	check(ArenaCache::lookupHashAll('missing') === null, 'child: lookupHashAll on missing record is null');

	ArenaCache::publish('from-child', ['pid' => 'child-wrote-this']);
	global $failures;
	exit($failures === 0 ? 0 : 1);
}

if ($mode === 'child-race') {
	// all racers publish the same key (same content) plus a distinct one
	$name = $argv[2];
	check(ArenaCache::attach($name), 'racer: attach');
	$payload = ['winner-takes' => str_repeat('all', 100), 'rows' => range(1, 50)];
	ArenaCache::publish('contested', $payload);
	ArenaCache::publish('racer-' . getmypid(), [getmypid()]);
	check(ArenaCache::lookup('contested') === $payload, 'racer: contested readback identical');
	global $failures;
	exit($failures === 0 ? 0 : 1);
}

if ($mode === 'child-late') {
	// spawned after unlink: attach must fail gracefully, lookups miss
	$name = $argv[2];
	check(!ArenaCache::attach($name), 'late child: attach fails after unlink');
	check(ArenaCache::lookup('fixtures') === null, 'late child: lookup misses without arena');
	check(!ArenaCache::hasRecord('fixtures'), 'late child: hasRecord false without arena');
	ArenaCache::publish('late', [1]); // must be a silent no-op
	global $failures;
	exit($failures === 0 ? 0 : 1);
}

if ($mode !== 'parent') {
	fwrite(STDERR, "unknown mode $mode\n");
	exit(2);
}

// ---- parent ----

$runId = 'smoke' . substr(bin2hex(random_bytes(6)), 0, 10);
$name = ArenaCache::create($runId);
check(is_string($name), 'parent: create returns name');
check(ArenaCache::create($runId) === null, 'parent: second create refused');
check(ArenaCache::create('bad id!') === null, 'parent: invalid run id refused');

ArenaCache::publish('fixtures', fixtures());
ArenaCache::publishHash('sigmap', hashFixtures());
ArenaCache::publish('rejected-object', new stdClass());
ArenaCache::publish('rejected-nested-object', ['a' => [new stdClass()]]);
check(!ArenaCache::hasRecord('rejected-object'), 'parent: object publish aborted');
check(!ArenaCache::hasRecord('rejected-nested-object'), 'parent: nested object publish aborted');
check(ArenaCache::lookup('fixtures') === fixtures(), 'parent: own fixtures readback');

// value with a reference inside: dereferenced transparently
$shared = 'refd';
$withRef = ['r' => &$shared];
ArenaCache::publish('with-ref', $withRef);
check(ArenaCache::lookup('with-ref') === ['r' => 'refd'], 'parent: references serialize by value');

// empty hash record: exists, every entry absent, enumerates to []
ArenaCache::publishHash('empty-hash', []);
check(ArenaCache::hasRecord('empty-hash'), 'parent: empty hash record exists');
check(ArenaCache::lookupHash('empty-hash', 'anything') === null, 'parent: empty hash record has no entries');
check(ArenaCache::lookupHashAll('empty-hash') === [], 'parent: empty hash record enumerates to []');

// duplicate publish of an existing key is a no-op, first write wins
ArenaCache::publish('fixtures', ['clobbered' => true]);
check(ArenaCache::lookup('fixtures') === fixtures(), 'parent: republish does not clobber');

// ---- child reads everything, writes back ----
[$exitCode, $stdout] = waitChild(spawnChild('child-read', $name));
echo $stdout;
check($exitCode === 0, 'child-read exit code 0');
check(ArenaCache::lookup('from-child') === ['pid' => 'child-wrote-this'], 'parent: sees child-published record');

// ---- racing publishers converge ----
$racers = [];
for ($i = 0; $i < 4; $i++) {
	$racers[] = spawnChild('child-race', $name);
}
foreach ($racers as $racer) {
	[$exitCode, $stdout] = waitChild($racer);
	echo $stdout;
	check($exitCode === 0, 'racer exit code 0');
}
$contested = ArenaCache::lookup('contested');
check($contested === ['winner-takes' => str_repeat('all', 100), 'rows' => range(1, 50)], 'parent: contested record consistent after race');

// ---- unlink: existing mappings keep working, new attaches fail ----
ArenaCache::unlinkName();
check(ArenaCache::lookup('fixtures') === fixtures(), 'parent: mapping alive after unlink');
[$exitCode, $stdout] = waitChild(spawnChild('child-late', $name));
echo $stdout;
check($exitCode === 0, 'child-late exit code 0');

// ---- destroy: everything misses, publishes are no-ops ----
ArenaCache::destroy();
check(ArenaCache::lookup('fixtures') === null, 'parent: lookup misses after destroy');
check(!ArenaCache::hasRecord('fixtures'), 'parent: hasRecord false after destroy');
ArenaCache::publish('post-destroy', [1]);
check(ArenaCache::lookup('post-destroy') === null, 'parent: publish after destroy is no-op');
ArenaCache::destroy(); // idempotent

if ($failures === 0) {
	echo "ALL OK\n";
	exit(0);
}
exit(1);
