<?php declare(strict_types=1);

/**
 * Differential test for Runtime::trustTypesUnder() (TrustedTypes.cpp).
 *
 * Two identical fixtures, one outside the trusted prefix and one under it,
 * are compiled in the same process — the second after the prefix is armed —
 * and each case passes a wrongly typed value through a parameter, a
 * default, a variadic, a return, a method, a closure, a typed property and
 * a promoted constructor property:
 *   control: every case throws TypeError (except the float cases, which
 *            coerce the int)
 *   trusted: parameter, default, return, method and closure checks are
 *            gone; the variadic, the property writes and the float
 *            signatures keep theirs — by design, see TrustedTypes.cpp
 *
 * Needs opcache: the pass runs inside the optimizer.
 * Run: php -d extension=$PWD/phpstan_turbo.so -d opcache.enable_cli=1 tests/trusted-types.php
 */

const CONTROL_EXPECTED = [
	'recv' => 'TypeError',
	'recvInit' => 'TypeError',
	'ret' => 'TypeError',
	'variadic' => 'TypeError',
	'toFloat' => '1.0',
	'retFloat' => '1.0',
	'method' => 'TypeError',
	'closure' => 'TypeError',
	'prop' => 'TypeError',
	'promoted' => 'TypeError',
];
const TRUSTED_EXPECTED = [
	'recv' => "'a'",
	'recvInit' => "'b'",
	'ret' => '1',
	'variadic' => 'TypeError',
	'toFloat' => '1.0',
	'retFloat' => '1.0',
	'method' => "'c'",
	'closure' => "'d'",
	'prop' => 'TypeError',
	'promoted' => 'TypeError',
];

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "phpstan_turbo extension is not loaded\n");
	exit(1);
}
if (!function_exists('opcache_get_status')) {
	echo "SKIP: opcache is not loaded\n";
	exit(0);
}
if (opcache_get_status(false) === false) {
	fwrite(STDERR, "opcache is not enabled - run with -d opcache.enable_cli=1\n");
	exit(1);
}

/**
 * @return array<string, string>
 */
function runCases(string $namespace): array
{
	$results = [];
	$cases = [
		'recv' => static fn () => ($namespace . '\\recv')('a'),
		'recvInit' => static fn () => ($namespace . '\\recvInit')('b'),
		'ret' => static fn () => ($namespace . '\\ret')(1),
		'variadic' => static fn () => ($namespace . '\\variadic')('x', 'y'),
		'toFloat' => static fn () => ($namespace . '\\toFloat')(1),
		'retFloat' => static fn () => ($namespace . '\\retFloat')(1),
		'method' => static fn () => (new ($namespace . '\\Holder')())->method('c'),
		'closure' => static fn () => (($namespace . '\\Holder')::closure())('d'),
		'prop' => static function () use ($namespace) {
			$holder = new ($namespace . '\\Holder')();
			$holder->prop = 'e';
			return $holder->prop;
		},
		'promoted' => static fn () => (new ($namespace . '\\Holder')('f'))->promoted,
	];
	foreach ($cases as $name => $case) {
		try {
			$results[$name] = var_export($case(), true);
		} catch (TypeError) {
			$results[$name] = 'TypeError';
		}
	}

	return $results;
}

/**
 * @param array<string, string> $expected
 * @param array<string, string> $actual
 */
function check(string $label, array $expected, array $actual): bool
{
	$ok = true;
	foreach ($expected as $name => $value) {
		$got = $actual[$name] ?? 'missing';
		if ($got !== $value) {
			fwrite(STDERR, sprintf("FAIL: %s %s: expected %s, got %s\n", $label, $name, $value, $got));
			$ok = false;
		}
	}
	printf("%-8s %s\n", $label . ':', json_encode($actual));

	return $ok;
}

$trustedPrefix = __DIR__ . '/trusted-types-fixtures/trusted/';

$ok = true;

require __DIR__ . '/trusted-types-fixtures/control/functions.php';
$ok = check('control', CONTROL_EXPECTED, runCases('TrustedTypesFixture\Control')) && $ok;

if (!PHPStanTurbo\Runtime::trustTypesUnder($trustedPrefix)) {
	fwrite(STDERR, "FAIL: trustTypesUnder() could not arm the pass (zend_optimizer_register_pass not resolvable?)\n");
	exit(1);
}
require $trustedPrefix . 'functions.php';
$ok = check('trusted', TRUSTED_EXPECTED, runCases('TrustedTypesFixture\Trusted')) && $ok;

if (!$ok) {
	exit(1);
}

echo "ALL OK\n";
