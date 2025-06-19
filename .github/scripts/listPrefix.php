<?php declare(strict_types = 1);

require_once __DIR__ . '/../../vendor/autoload.php';

$dir = $argv[1] ?? __DIR__;
$iterator = new RecursiveDirectoryIterator($dir);
$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator);

$locations = [];
foreach ($files as $file) {
	$path = $file->getPathname();
	if ($file->getExtension() !== 'php') {
		continue;
	}
	$contents = file_get_contents($path);
	$lines = explode("\n", $contents);
	foreach ($lines as $i => $line) {
		if (!str_contains($line, '_PHPStan_checksum')) {
			continue;
		}

		$trimmedPath = substr($path, strlen($dir) + 1);
		if (str_starts_with($trimmedPath, 'vendor/composer/autoload_')) {
			continue;
		}
		$locations[] = $trimmedPath . ':' . ($i + 1);
	}
}
sort($locations);
echo implode("\n", $locations);
echo "\n";
