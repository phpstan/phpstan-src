<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\FileFix;
use function count;

/**
 * @internal
 */
final class FileFixAggregator
{

	/**
	 * @param array<string, array<string, FileFix>> $perAnalysedFileFixes
	 * @return array<string, FileFix>
	 */
	public static function aggregate(array $perAnalysedFileFixes): array
	{
		$grouped = [];
		foreach ($perAnalysedFileFixes as $perFileFixes) {
			foreach ($perFileFixes as $fixingFile => $fileFix) {
				$grouped[$fixingFile][] = $fileFix;
			}
		}

		$result = [];
		foreach ($grouped as $fixingFile => $candidates) {
			$first = $candidates[0];

			if (count($candidates) === 1) {
				$result[$fixingFile] = $first;
				continue;
			}

			$expectedHash = $first->diff->originalHash;
			$conflict = false;

			foreach ($candidates as $candidate) {
				if ($candidate->diff->originalHash !== $expectedHash) {
					$conflict = true;
					break;
				}
			}

			if ($conflict) {
				continue;
			}

			$mergedRefs = [];
			$seen = [];
			foreach ($candidates as $candidate) {
				foreach ($candidate->errorRefs as $ref) {
					$key = $ref['line'] . ':' . ($ref['identifier'] ?? '');
					if (isset($seen[$key])) {
						continue;
					}
					$seen[$key] = true;
					$mergedRefs[] = $ref;
				}
			}

			$result[$fixingFile] = new FileFix($first->diff, $mergedRefs);
		}

		return $result;
	}

}
