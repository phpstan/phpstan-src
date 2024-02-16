<?php

$testList = shell_exec('php vendor/bin/phpunit --list-tests');

if (!is_string($testList)) {
	throw new RuntimeException('Error while listing tests');
}

$testFilters = [];
foreach(explode("\n", $testList) as $line) {
	$cleanedLine = trim($line, ' -');

	if ($cleanedLine === '') {
		continue;
	}

	if (
		!str_contains($cleanedLine, 'PHPStan\Generics\GenericsIntegrationTest') &&
		!str_contains($cleanedLine, 'PHPStan\Levels\LevelsIntegrationTest')
	) {
		continue;
	}

	$cleanedLine = str_replace('\\', '\\\\', $cleanedLine);

	$testFilters[] = $cleanedLine;
}

if ($testFilters === []) {
	throw new RuntimeException('No tests found');
}

echo json_encode($testFilters);
