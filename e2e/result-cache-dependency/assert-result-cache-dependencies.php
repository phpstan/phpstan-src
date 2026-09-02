<?php

declare(strict_types=1);

use PHPStan\Collectors\ResultCacheDependencyCollector;

$cache = require __DIR__ . '/tmp/resultCache.php';
$collectedData = $cache['collectedDataCallback']();
$recordCount = 0;
foreach ($collectedData as $file => $collectedDataForFile) {
	$records = $collectedDataForFile[ResultCacheDependencyCollector::class] ?? [];
	$seen = [];
	foreach ($records as $record) {
		$identity = $record['extensionKey'] . "\0" . $record['dependencyKey'];
		if (isset($seen[$identity])) {
			throw new RuntimeException(sprintf('Duplicate result-cache dependency persisted for %s.', $file));
		}
		$seen[$identity] = true;
		if ($record['hash'] === 'extension-supplied') {
			throw new RuntimeException(sprintf('Extension-supplied hash persisted for %s.', $file));
		}
		$recordCount++;
	}
}

if ($recordCount !== 6) {
	throw new RuntimeException(sprintf('Expected 6 persisted dependency records, got %d.', $recordCount));
}

echo "result cache contains six unique main-process dependency hashes.\n";
