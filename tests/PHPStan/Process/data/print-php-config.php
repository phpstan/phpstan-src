<?php declare(strict_types = 1);

// Prints the PHP configuration of this process and of a child process started
// with InheritedPhpConfig::getArgs() - the two have to be the same, see
// InheritedPhpConfigTest::testChildProcessRepeatsThePhpConfigurationOfItsParent().

require_once __DIR__ . '/../../../../src/Process/InheritedPhpConfig.php';

$readConfig = static fn (): array => [
	'loadedIniFile' => php_ini_loaded_file(),
	'scannedIniFiles' => php_ini_scanned_files() !== false,
	'xdebugMode' => get_cfg_var('xdebug.mode'),
	'tempDir' => sys_get_temp_dir(),
];

if (($argv[1] ?? '') === 'child') {
	echo json_encode($readConfig());

	return;
}

$command = implode(' ', array_merge(
	[escapeshellarg(PHP_BINARY)],
	array_map(static fn (string $arg): string => escapeshellarg($arg), PHPStan\Process\InheritedPhpConfig::getArgs()),
	[escapeshellarg(__FILE__), 'child'],
));

echo json_encode([
	'parent' => $readConfig(),
	'child' => json_decode((string) shell_exec($command), true),
]);
