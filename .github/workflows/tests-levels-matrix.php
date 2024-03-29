<?php declare(strict_types = 1);

shell_exec('php vendor/bin/phpunit --list-tests-xml test-list.xml');

$simpleXml = simplexml_load_file('test-list.xml');
if ($simpleXml === false) {
	throw new RuntimeException('Error loading test-list.xml');
}

$testFilters = [];
foreach($simpleXml->testCaseClass as $testCaseClass) {
	foreach($testCaseClass->testCaseMethod as $testCaseMethod) {
		if ((string) $testCaseMethod['groups'] !== 'levels') {
			continue;
		}

		$testCaseName = (string) $testCaseMethod['id'];

		[$className, $testName] = explode('::', $testCaseName, 2);
		$fileName = 'tests/'. str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

		$filter = str_replace('\\', '\\\\', $testCaseName);

		$testFilters[] = sprintf("%s --filter %s", escapeshellarg($fileName), escapeshellarg($filter));
	}
}

if ($testFilters === []) {
	throw new RuntimeException('No tests found');
}

$chunkSize = (int) ceil(count($testFilters) / 10);
$chunks = array_chunk($testFilters, $chunkSize);

$commands = [];
foreach ($chunks as $chunk) {
	$commands[] = implode("\n", array_map(fn (string $ch) => sprintf('php vendor/bin/phpunit %s --group levels', $ch), $chunk));
}

echo json_encode($commands);
