<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use function array_count_values;
use function array_slice;
use function arsort;

final class ProcessedFilesCollector
{

	/** @var list<string> */
	private array $processedFiles = [];

	/**
	 * @param list<string> $files
	 */
	public function addProcessedFiles(array $files): void
	{
		foreach ($files as $file) {
			$this->processedFiles[] = $file;
		}
	}

	/**
	 * @return array<string, int>
	 */
	public function getTopMostAnalysedFiles(int $limit): array
	{
		$counts = array_count_values($this->processedFiles);
		arsort($counts);

		$result = [];
		foreach (array_slice($counts, 0, $limit, true) as $file => $count) {
			if ($count <= 1) {
				continue;
			}
			$result[$file] = $count;
		}

		return $result;
	}

}
