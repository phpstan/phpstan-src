<?php

declare(strict_types=1);

$cacheFile = __DIR__ . '/tmp/resultCache.php';
$contents = file_get_contents($cacheFile);
if ($contents === false) {
	throw new RuntimeException('Could not read result cache.');
}

$mutation = $argv[1] ?? null;
if ($mutation === 'malformed-payload') {
	$cache = require $cacheFile;
	$collectedData = $cache['collectedDataCallback']();
	$collectorType = 'PHPStan\\Collectors\\ResultCacheDependencyCollector';
	foreach ($collectedData as $file => $collectedDataPerFile) {
		if (!array_key_exists($collectorType, $collectedDataPerFile)) {
			continue;
		}

		$search = substr(var_export([$file => $collectedDataPerFile], true), 8, -2);
		$collectedDataPerFile[$collectorType] = 'malformed';
		$replacement = substr(var_export([$file => $collectedDataPerFile], true), 8, -2);
		break;
	}
} else {
	[$search, $replacement] = match ($mutation) {
		'unknown-provider' => [
			"'extensionKey' => " . var_export('ResultCacheE2E\\Dependency\\ConfigTypeRegistry', true),
			"'extensionKey' => 'missing-extension'",
		],
		'malformed-record' => [
			"'dependencyKey' => 'replacement'",
			"'dependencyKey' => array ()",
		],
		default => throw new RuntimeException('Unknown mutation.'),
	};
}

if (!isset($search, $replacement)) {
	throw new RuntimeException('Result cache did not contain dependency collected data.');
}

$contents = str_replace($search, $replacement, $contents, $count);
if ($count === 0) {
	throw new RuntimeException('Result-cache mutation did not match anything.');
}
if (file_put_contents($cacheFile, $contents) === false) {
	throw new RuntimeException('Could not write result cache.');
}
