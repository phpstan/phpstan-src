<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use function array_splice;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_quote;
use function sprintf;
use function usort;

/**
 * Removes converted entries from a NEON file and declares the attributeServicesDirectories
 * section - by plain line surgery, so every untouched line stays byte-identical. Entries
 * are matched to the decoded structure by their order in the file; a count mismatch aborts
 * instead of guessing.
 */
final class NeonEditor
{

	/**
	 * @param list<int> $entryIndexes
	 * @throws Neon2AttributesException
	 */
	public function removeEntries(string $content, string $section, array $entryIndexes, int $expectedEntryCount): string
	{
		if (count($entryIndexes) === 0) {
			return $content;
		}

		$lines = explode("\n", $content);
		[$headerLine, $sectionEnd] = $this->findSection($lines, $section);

		$entryStarts = [];
		$entryIndent = null;
		for ($i = $headerLine + 1; $i < $sectionEnd; $i++) {
			$line = $lines[$i];
			if (preg_match('/^(\s+)(\S)/', $line, $matches) !== 1) {
				continue;
			}
			if ($matches[2] === '#') {
				continue;
			}

			$entryIndent ??= $matches[1];
			if ($matches[1] !== $entryIndent) {
				continue;
			}

			$entryStarts[] = $i;
		}

		if (count($entryStarts) !== $expectedEntryCount) {
			throw new Neon2AttributesException(sprintf(
				'Cannot map the `%s` section onto the file - found %d entries in the text but the decoded section has %d. The file layout is too unusual for automatic editing.',
				$section,
				count($entryStarts),
				$expectedEntryCount,
			));
		}

		$ranges = [];
		foreach ($entryIndexes as $entryIndex) {
			if (!isset($entryStarts[$entryIndex])) {
				throw new Neon2AttributesException(sprintf('Entry #%d not found in the `%s` section.', $entryIndex, $section));
			}

			$start = $entryStarts[$entryIndex];
			$end = $entryStarts[$entryIndex + 1] ?? $sectionEnd;
			$ranges[] = [$start, $end];
		}

		if (count($entryIndexes) === $expectedEntryCount) {
			// the whole section goes away, header included
			$ranges = [[$headerLine, $sectionEnd]];
		}

		usort($ranges, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
		foreach ($ranges as [$start, $end]) {
			array_splice($lines, $start, $end - $start);
		}

		return implode("\n", $lines);
	}

	/**
	 * @param list<string> $directories relative to the NEON file
	 * @throws Neon2AttributesException
	 */
	public function addDirectoriesSection(string $content, array $directories): string
	{
		if (count($directories) === 0) {
			return $content;
		}

		$lines = explode("\n", $content);
		$indent = $this->detectIndent($lines);

		$existingHeader = null;
		foreach ($lines as $i => $line) {
			if (preg_match('/^attributeServicesDirectories:\s*(#.*)?$/', $line) !== 1) {
				continue;
			}

			$existingHeader = $i;
			break;
		}

		if ($existingHeader !== null) {
			[$headerLine, $sectionEnd] = $this->findSection($lines, 'attributeServicesDirectories');
			$existingEntries = [];
			for ($i = $headerLine + 1; $i < $sectionEnd; $i++) {
				if (preg_match('/^\s+-\s*(.+?)\s*$/', $lines[$i], $matches) !== 1) {
					continue;
				}

				$existingEntries[] = $matches[1];
			}

			$newLines = [];
			foreach ($directories as $directory) {
				if (in_array($directory, $existingEntries, true)) {
					continue;
				}

				$newLines[] = $indent . '- ' . $directory;
			}

			// insert right after the last existing entry (before any trailing blank lines)
			$insertAt = $headerLine + 1;
			for ($i = $headerLine + 1; $i < $sectionEnd; $i++) {
				if ($lines[$i] === '') {
					continue;
				}

				$insertAt = $i + 1;
			}
			array_splice($lines, $insertAt, 0, $newLines);

			return implode("\n", $lines);
		}

		$newLines = ['attributeServicesDirectories:'];
		foreach ($directories as $directory) {
			$newLines[] = $indent . '- ' . $directory;
		}
		$newLines[] = '';

		array_splice($lines, 0, 0, $newLines);

		return implode("\n", $lines);
	}

	/**
	 * @param list<string> $lines
	 * @return array{int, int} header line index, exclusive section end index
	 * @throws Neon2AttributesException
	 */
	private function findSection(array $lines, string $section): array
	{
		$headerLine = null;
		foreach ($lines as $i => $line) {
			if (preg_match(sprintf('/^%s:\s*(#.*)?$/', preg_quote($section, '/')), $line) !== 1) {
				continue;
			}

			$headerLine = $i;
			break;
		}

		if ($headerLine === null) {
			throw new Neon2AttributesException(sprintf('Cannot find the `%s` section in the file.', $section));
		}

		$sectionEnd = count($lines);
		for ($i = $headerLine + 1; $i < count($lines); $i++) {
			if (preg_match('/^[^\s#]/', $lines[$i]) !== 1) {
				continue;
			}

			$sectionEnd = $i;
			break;
		}

		return [$headerLine, $sectionEnd];
	}

	/**
	 * @param list<string> $lines
	 */
	private function detectIndent(array $lines): string
	{
		foreach ($lines as $line) {
			if (preg_match('/^(\t+| +)\S/', $line, $matches) !== 1) {
				continue;
			}

			return $matches[1];
		}

		return "\t";
	}

}
