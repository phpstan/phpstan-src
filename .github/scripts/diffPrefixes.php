<?php declare(strict_types = 1);

use Nette\Utils\Strings;
use SebastianBergmann\Diff\Differ;

require_once __DIR__ . '/../../vendor/autoload.php';

$diffFile = $argv[1] ?? null;
$oldDir = $argv[2] ?? null;
$newDir = $argv[3] ?? null;
if ($diffFile === null || !is_file($diffFile)) {
	exit(1);
}

if ($oldDir === null || !is_dir($oldDir)) {
	exit(1);
}

if ($newDir === null || !is_dir($newDir)) {
	exit(1);
}

$diffLines = explode("\n", file_get_contents($diffFile));
$differ = new Differ(new \SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder('', true));
$isDifferent = false;
foreach ($diffLines as $diffLine) {
	$operation = $diffLine[0];
	if ($operation === ' ') {
		continue;
	}

	$pathWithLine = substr($diffLine, 1);
	$pathParts = explode(':', $pathWithLine);
	$path = $pathParts[0];
	$lineNumber = $pathParts[1] ?? null;
	if ($lineNumber === null) {
		continue;
	}
	$oldFilePath = $oldDir . '/' . $path;
	if (!is_file($oldFilePath)) {
		continue;
	}

	$newFilePath = $newDir . '/' . $path;
	if (!is_file($newFilePath)) {
		continue;
	}

	$stringDiff = $differ->diff(file_get_contents($oldFilePath), file_get_contents($newFilePath));
	if ($stringDiff === '') {
		continue;
	}

	$isDifferent = true;

	echo "$path:\n";
	$startLine = 1;
	$startContext = 1;
	foreach (explode("\n", $stringDiff) as $i => $line) {
		$matches = Strings::match($line, '/^@@ -(\d+),?(\d*) \+(\d+),?(\d*) @@/');
		if ($matches !== null) {
			$startLine = (int) $matches[1];
			$startContext = (int) $matches[2];
			continue;
		}

		if ($lineNumber < $startLine || $lineNumber > ($startLine + $startContext)) {
			continue;
		}

		if (str_starts_with($line, '+')) {
			echo "\033[32m$line\033[0m\n";
		} elseif (str_starts_with($line, '-')) {
			echo "\033[31m$line\033[0m\n";
		} else {
			echo "$line\n";
		}
	}

	echo "\n";
}

if ($isDifferent) {
	exit(1);
}
