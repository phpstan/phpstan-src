<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use function array_key_exists;
use function array_values;
use function count;
use function is_array;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
final class LocalIgnoresProcessor
{

	/**
	 * @param list<Error> $temporaryFileErrors
	 * @param LinesToIgnore $linesToIgnore
	 * @param LinesToIgnore $unmatchedLineIgnores
	 */
	public function process(
		array $temporaryFileErrors,
		array $linesToIgnore,
		array $unmatchedLineIgnores,
	): LocalIgnoresProcessorResult
	{
		$fileErrors = [];
		$locallyIgnoredErrors = [];
		foreach ($temporaryFileErrors as $tmpFileError) {
			$line = $tmpFileError->getLine();
			if (
				$line !== null
				&& $tmpFileError->canBeIgnored()
				&& array_key_exists($tmpFileError->getFile(), $linesToIgnore)
				&& array_key_exists($line, $linesToIgnore[$tmpFileError->getFile()])
			) {
				$identifiers = $linesToIgnore[$tmpFileError->getFile()][$line];
				if ($identifiers === null) {
					$locallyIgnoredErrors[] = $tmpFileError;
					unset($unmatchedLineIgnores[$tmpFileError->getFile()][$line]);
					continue;
				}

				if ($tmpFileError->getIdentifier() === null) {
					$fileErrors[] = $tmpFileError;
					continue;
				}

				foreach ($identifiers as $i => $ignoredIdentifier) {
					if ($ignoredIdentifier !== $tmpFileError->getIdentifier()) {
						continue;
					}

					unset($identifiers[$i]);

					if (count($identifiers) > 0) {
						$linesToIgnore[$tmpFileError->getFile()][$line] = array_values($identifiers);
					} else {
						unset($linesToIgnore[$tmpFileError->getFile()][$line]);
					}

					if (
						array_key_exists($tmpFileError->getFile(), $unmatchedLineIgnores)
						&& array_key_exists($line, $unmatchedLineIgnores[$tmpFileError->getFile()])
					) {
						$unmatchedIgnoredIdentifiers = $unmatchedLineIgnores[$tmpFileError->getFile()][$line];
						if (is_array($unmatchedIgnoredIdentifiers)) {
							foreach ($unmatchedIgnoredIdentifiers as $j => $unmatchedIgnoredIdentifier) {
								if ($ignoredIdentifier !== $unmatchedIgnoredIdentifier) {
									continue;
								}

								unset($unmatchedIgnoredIdentifiers[$j]);

								if (count($unmatchedIgnoredIdentifiers) > 0) {
									$unmatchedLineIgnores[$tmpFileError->getFile()][$line] = array_values($unmatchedIgnoredIdentifiers);
								} else {
									unset($unmatchedLineIgnores[$tmpFileError->getFile()][$line]);
								}
								break;
							}
						}
					}

					$locallyIgnoredErrors[] = $tmpFileError;
					continue 2;
				}
			}

			$fileErrors[] = $tmpFileError;
		}

		return new LocalIgnoresProcessorResult(
			$fileErrors,
			$locallyIgnoredErrors,
			$linesToIgnore,
			$unmatchedLineIgnores,
		);
	}

}
