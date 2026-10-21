<?php declare(strict_types = 1);

/**
 * Asserts what the probe recorded about the process the analysis really ran in.
 *
 * Usage: php check.php <expectation>=<value> ...
 *
 * restarted-for=pcov,xdebug  the marker every process must carry, "" for "no restart happened"
 * xdebug-armed=no            whether xdebug's hooks may be installed (effective mode is not off)
 * pcov-armed=no              whether pcov's call hook may be installed
 * uopz-armed=no              whether uopz may be rewriting opcodes
 * opcache-cli-on=no          whether OPcache may be enabled for CLI
 * xdebug-loaded=yes          whether the extension may be *present* - the flag rows disarm
 *                            rather than unload, so this stays yes where xdebug-armed=no
 * processes=2+               how many processes the probe must have seen
 * starts=2                   how often the main process must have started, counted by
 *                            start-log.php: 2 is one restart, more would be a second one
 */

$log = __DIR__ . '/probe.log';
if (!is_file($log)) {
	fwrite(STDERR, "FAIL: the probe never ran - no probe.log\n");
	exit(1);
}

$contents = file_get_contents($log);
if ($contents === false) {
	fwrite(STDERR, "FAIL: probe.log is not readable\n");
	exit(1);
}

$records = [];
foreach (explode("\n", trim($contents)) as $line) {
	if ($line === '') {
		continue;
	}

	$records[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
}

$expectations = [];
foreach (array_slice($argv, 1) as $argument) {
	[$name, $value] = explode('=', $argument, 2);
	$expectations[$name] = $value;
}

/** Whether each switch is armed, given one probe record. */
$isArmed = [
	'xdebug-armed' => static function (array $record): bool {
		if ($record['xdebug_loaded'] !== true) {
			return false;
		}

		foreach (explode(',', str_replace(' ', '', strtolower($record['xdebug_mode']))) as $mode) {
			if ($mode !== '' && $mode !== 'off') {
				return true;
			}
		}

		return false;
	},
	'pcov-armed' => static fn (array $record): bool => $record['pcov_loaded'] === true
		&& !in_array($record['pcov_enabled'], ['', '0'], true),
	'uopz-armed' => static fn (array $record): bool => $record['uopz_loaded'] === true
		&& in_array($record['uopz_disable'], ['', '0'], true),
	'opcache-cli-on' => static fn (array $record): bool => !in_array($record['opcache_cli'], ['', '0'], true),
];

$isPresent = [
	'xdebug-loaded' => static fn (array $record): bool => $record['xdebug_loaded'] === true,
	'pcov-loaded' => static fn (array $record): bool => $record['pcov_loaded'] === true,
];

$failures = [];

$minimumProcesses = (int) rtrim($expectations['processes'] ?? '1+', '+');
if (count($records) < $minimumProcesses) {
	$failures[] = sprintf('expected at least %d processes, the probe saw %d', $minimumProcesses, count($records));
}

foreach ($records as $index => $record) {
	$where = sprintf('process %d (pid %s)', $index + 1, $record['pid']);

	if (array_key_exists('restarted-for', $expectations) && $record['restarted_for'] !== $expectations['restarted-for']) {
		$failures[] = sprintf(
			'%s: PHPSTAN_RESTART is "%s", expected "%s"',
			$where,
			$record['restarted_for'],
			$expectations['restarted-for'],
		);
	}

	foreach ($isArmed + $isPresent as $name => $predicate) {
		if (!array_key_exists($name, $expectations)) {
			continue;
		}

		$expected = $expectations[$name] === 'yes';
		if ($predicate($record) === $expected) {
			continue;
		}

		$failures[] = sprintf('%s: %s is %s, expected %s', $where, $name, $expected ? 'no' : 'yes', $expectations[$name]);
	}

	// the pointer to the composed ini must never reach a descendant, or a
	// worker would delete the file its siblings are still reading
	if ($index > 0 && $record['restart_ini_pointer'] !== '') {
		$failures[] = sprintf('%s: inherited PHPSTAN_RESTART_INI="%s"', $where, $record['restart_ini_pointer']);
	}
}

if (array_key_exists('starts', $expectations)) {
	$startsLog = __DIR__ . '/starts.log';
	$starts = is_file($startsLog) ? count(file($startsLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
	if ($starts !== (int) $expectations['starts']) {
		$failures[] = sprintf('the main process started %d times, expected %s', $starts, $expectations['starts']);
	}
}

// a second restart would compose a second ini and leak the first one
$leftovers = glob(sys_get_temp_dir() . '/phpstan-restart-ini-*');
if ($leftovers !== false && $leftovers !== []) {
	$failures[] = sprintf('composed ini files left behind: %s', implode(', ', $leftovers));
}

foreach ($records as $record) {
	echo json_encode($record, JSON_THROW_ON_ERROR), "\n";
}

if ($failures !== []) {
	fwrite(STDERR, "FAIL:\n - " . implode("\n - ", $failures) . "\n");
	exit(1);
}

echo sprintf("OK: %d processes checked\n", count($records));
