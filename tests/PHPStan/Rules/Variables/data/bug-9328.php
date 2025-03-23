<?php

namespace Bug9328;

function (): void {
	$contents = file('foo.ini', FILE_IGNORE_NEW_LINES);
	if (false === $contents) {
		throw new \Exception('Failed to read file');
	}
	// Add section so that the last one is flushed:
	$contents[] = '[--]';
	$currentSection = '';
	$sections = [];
	$lines = [];
	foreach ($contents as $line) {
		if (
			str_starts_with($line, '[')
			&& str_ends_with($line, ']')
			&& strlen($line) > 2
		) {
			// flush previously collected section:
			if ($lines) {
				$sections[] = [
					'name' => $currentSection,
					'lines' => $lines,
				];
			}
			$currentSection = substr($line, 1, -1);
			$lines = [];
		}
		$lines[] = $line;
	}

	if (isset($sections[1])) {
		echo "We have multiple remaining sections!\n";
	}
};
