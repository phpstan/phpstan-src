<?php declare(strict_types = 1);

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

	[$className, $testName] = explode('::', $cleanedLine, 2);
	$fileName = 'tests/'. str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

	$filter = str_replace('\\', '\\\\', $cleanedLine);

	$testFilters[] = sprintf("%s --filter '%s'", $fileName, $filter);
}

if ($testFilters === []) {
	throw new RuntimeException('No tests found');
}

echo json_encode($testFilters);
