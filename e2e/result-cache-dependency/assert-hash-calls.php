<?php

declare(strict_types=1);

$phase = $argv[1] ?? null;
if ($phase !== 'cold' && $phase !== 'warm') {
	throw new RuntimeException('Expected a cold or warm phase.');
}

$mainPid = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
if (!is_int($mainPid) || $mainPid <= 0) {
	throw new RuntimeException('Expected the PHPStan main-process PID.');
}

$hashCallLines = file(__DIR__ . '/tmp/hash-calls.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($hashCallLines === false) {
	throw new RuntimeException('Could not read dependency hash calls.');
}

$hashCallPids = [];
$hashCallDependencies = [];
foreach ($hashCallLines as $line) {
	if (preg_match('/^(\d+) (\S+) (.+)$/D', $line, $matches) !== 1) {
		throw new RuntimeException(sprintf('Malformed dependency hash call: %s', $line));
	}

	$hashCallPids[] = (int) $matches[1];
	$hashCallDependencies[] = sprintf('%s %s', $matches[2], $matches[3]);
}

$expectedDependencies = [
	'ResultCacheE2E\\Dependency\\ConfigTypeRegistry checkout.label',
	'ResultCacheE2E\\Dependency\\ConfigTypeRegistry database.connection.legacy',
	'ResultCacheE2E\\Dependency\\ConfigTypeRegistry database.default',
	'ResultCacheE2E\\Dependency\\ConfigTypeRegistry profile.name',
	'ResultCacheE2E\\Dependency\\TenantConfigTypeRegistry checkout.label',
];
sort($hashCallDependencies);
if ($hashCallDependencies !== $expectedDependencies) {
	throw new RuntimeException(sprintf(
		'Expected one hash call for each unique provider and dependency key, got: %s',
		implode(', ', $hashCallDependencies),
	));
}

$uniqueHashCallPids = array_values(array_unique($hashCallPids));
if ($uniqueHashCallPids !== [$mainPid]) {
	throw new RuntimeException(sprintf(
		'Expected dependency hashes to be calculated by main process %d, got: %s',
		$mainPid,
		implode(', ', $uniqueHashCallPids),
	));
}

$rulePidLines = file(__DIR__ . '/tmp/rule-pids.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($rulePidLines === false) {
	throw new RuntimeException('Could not read semantic dependency rule processes.');
}
$rulePids = [];
foreach ($rulePidLines as $line) {
	if (preg_match('/^\d+$/D', $line) !== 1) {
		throw new RuntimeException(sprintf('Malformed semantic dependency rule process: %s', $line));
	}
	$rulePids[] = (int) $line;
}

if ($phase === 'cold') {
	if ($rulePids === []) {
		throw new RuntimeException('Expected semantic dependency rules to run during cold analysis.');
	}
	if (in_array($mainPid, $rulePids, true)) {
		throw new RuntimeException('Expected semantic dependency rules to run in worker processes.');
	}
} elseif ($rulePids !== []) {
	throw new RuntimeException('Expected no semantic dependency rules during warm cache restoration.');
}

printf(
	"%s cache phase calculated five unique dependency hashes in main process %d.\n",
	$phase,
	$mainPid,
);
