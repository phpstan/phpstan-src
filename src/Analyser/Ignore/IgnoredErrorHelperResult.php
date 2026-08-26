<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_string;
use function spl_object_id;
use function sprintf;

/**
 * `IgnoredErrorHelper` may collapse several configured ignores into one
 * merged entry, so `message`/`rawMessage`/`identifier` are nullable here.
 * It also attaches `realPath` once the configured path is resolved. The
 * `messages`/`rawMessages`/`identifiers` keys remain in the inferred shape
 * even after expansion + unset (PHPStan does not strip optional keys via
 * negative isset on sealed shapes), so the type lists them explicitly here
 * — they are never read, only tolerated. `paths` is `array<int<0, max>,
 * string>` rather than `list<string>` because `process()` unsets matched
 * entries by index, breaking list-ness.
 *
 * @phpstan-type ExpandedIgnoredErrorData = array{
 *     message?: string|null,
 *     rawMessage?: string|null,
 *     identifier?: string|null,
 *     messages?: list<string>,
 *     rawMessages?: list<string>,
 *     identifiers?: list<string>,
 *     path?: string,
 *     paths?: array<int<0, max>, string>,
 *     count?: int,
 *     reportUnmatched?: bool,
 *     realPath?: string,
 * }
 */
final class IgnoredErrorHelperResult
{

	/**
	 * @param list<string> $errors
	 * @param array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}> $otherIgnoreErrors
	 * @param array<string, array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}>> $ignoreErrorsByFile
	 * @param (string|ExpandedIgnoredErrorData)[] $ignoreErrors
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private array $errors,
		private array $otherIgnoreErrors,
		private array $ignoreErrorsByFile,
		private array $ignoreErrors,
		private bool $reportUnmatchedIgnoredErrors,
	)
	{
	}

	/**
	 * @return list<string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @param list<Error> $errors
	 * @param string[] $analysedFiles
	 */
	public function process(
		array $errors,
		bool $onlyFiles,
		array $analysedFiles,
		bool $hasInternalErrors,
	): IgnoredErrorHelperProcessedResult
	{
		$unmatchedIgnoredErrors = $this->ignoreErrors;
		$stringErrors = [];

		// Per-entry runtime state for `count`-bounded ignores. Tracked in side
		// maps keyed by the same index so `$unmatchedIgnoredErrors` keeps the
		// `(string|ExpandedIgnoredErrorData)[]` shape across the closure's
		// offset writes — otherwise PHPStan widens it to `array<mixed>`.
		$realCounts = [];
		$matchedAt = [];

		$processIgnoreError = function (Error $error, int $i, $ignore) use (&$unmatchedIgnoredErrors, &$stringErrors, &$realCounts, &$matchedAt): bool {
			$shouldBeIgnored = false;
			if (is_string($ignore)) {
				$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, ignoredErrorPattern: $ignore, ignoredErrorMessage: null, identifier: null, path: null);
				if ($shouldBeIgnored) {
					unset($unmatchedIgnoredErrors[$i]);
				}
			} else {
				if (isset($ignore['path'])) {
					$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, ignoredErrorPattern: $ignore['message'] ?? null, ignoredErrorMessage: $ignore['rawMessage'] ?? null, identifier: $ignore['identifier'] ?? null, path: $ignore['path']);
					if ($shouldBeIgnored) {
						if (isset($ignore['count'])) {
							$realCount = ($realCounts[$i] ?? 0) + 1;
							$realCounts[$i] = $realCount;

							if (!isset($matchedAt[$i])) {
								$matchedAt[$i] = ['file' => $error->getFile(), 'line' => $error->getLine()];
							}

							if ($realCount > $ignore['count']) {
								$shouldBeIgnored = false;
							}
						} else {
							unset($unmatchedIgnoredErrors[$i]);
						}
					}
				} elseif (isset($ignore['paths'])) {
					foreach ($ignore['paths'] as $j => $ignorePath) {
						$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, ignoredErrorPattern: $ignore['message'] ?? null, ignoredErrorMessage: $ignore['rawMessage'] ?? null, identifier: $ignore['identifier'] ?? null, path: $ignorePath);
						if (!$shouldBeIgnored) {
							continue;
						}

						if (isset($unmatchedIgnoredErrors[$i])) {
							if (!is_array($unmatchedIgnoredErrors[$i])) {
								throw new ShouldNotHappenException();
							}
							unset($unmatchedIgnoredErrors[$i]['paths'][$j]);
							if (isset($unmatchedIgnoredErrors[$i]['paths']) && count($unmatchedIgnoredErrors[$i]['paths']) === 0) {
								unset($unmatchedIgnoredErrors[$i]);
							}
						}
						break;
					}
				} else {
					$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, ignoredErrorPattern: $ignore['message'] ?? null, ignoredErrorMessage: $ignore['rawMessage'] ?? null, identifier: $ignore['identifier'] ?? null, path: null);
					if ($shouldBeIgnored) {
						unset($unmatchedIgnoredErrors[$i]);
					}
				}
			}

			if ($shouldBeIgnored) {
				if (!$error->canBeIgnored()) {
					$stringErrors[] = sprintf(
						'Error message "%s" cannot be ignored, use excludePaths instead.',
						$error->getMessage(),
					);
					return true;
				}
				return false;
			}

			return true;
		};

		$ignoredErrors = [];
		$notIgnoredErrors = [];
		$strippedSurvivors = [];
		$errorQueue = $errors;
		for ($errorIndex = 0; $errorIndex < count($errorQueue); $errorIndex++) {
			$error = $errorQueue[$errorIndex];

			// An error deduplicated directly into a trait (see ConstantConditionInTraitRule)
			// stands for one occurrence per using class. An ignoreErrors path pointing at one
			// of the using classes accounts only for that class's context - it must not hide
			// the occurrences of the remaining contexts, which are re-queued and reported
			// (or further ignored) each in its own context.
			$traitContexts = $error->getTraitContexts();
			if (count($traitContexts) > 0) {
				$errorTraitFilePath = $error->getTraitFilePath();
				$remainingContexts = $traitContexts;
				foreach (array_keys($traitContexts) as $contextFilePath) {
					$contextError = $error->asReportedInTraitContext($contextFilePath);
					$contextIgnored = false;
					$normalizedContextFilePath = $this->fileHelper->normalizePath($contextFilePath);
					foreach ($this->ignoreErrorsByFile[$normalizedContextFilePath] ?? [] as $ignoreError) {
						$i = $ignoreError['index'];
						$ignore = $ignoreError['ignoreError'];
						if (!$processIgnoreError($contextError, $i, $ignore)) {
							$ignoredErrors[] = [$contextError, $ignore];
							$contextIgnored = true;
							break;
						}
					}
					if (!$contextIgnored) {
						foreach ($this->otherIgnoreErrors as $ignoreError) {
							$i = $ignoreError['index'];
							$ignore = $ignoreError['ignoreError'];
							// only entries scoped to a path that does not cover the trait file
							// itself act per-context - everything else (pathless entries, paths
							// covering the trait) matches the deduplicated error as a whole below
							if (!is_array($ignore) || !isset($ignore['path'])) {
								continue;
							}
							if (
								$errorTraitFilePath !== null
								&& (new FileExcluder($this->fileHelper, [$ignore['path']]))->isExcludedFromAnalysing($errorTraitFilePath)
							) {
								continue;
							}
							if (!$processIgnoreError($contextError, $i, $ignore)) {
								$ignoredErrors[] = [$contextError, $ignore];
								$contextIgnored = true;
								break;
							}
						}
					}
					if (!$contextIgnored) {
						continue;
					}

					unset($remainingContexts[$contextFilePath]);
				}

				if (count($remainingContexts) < count($traitContexts)) {
					foreach (array_keys($remainingContexts) as $contextFilePath) {
						$survivor = $error->asReportedInTraitContext($contextFilePath);
						// the strip phase above already tried this occurrence against its
						// file's entries and the context-scoped path entries - it must not
						// be counted against them a second time below
						$strippedSurvivors[spl_object_id($survivor)] = true;
						$errorQueue[] = $survivor;
					}
					continue;
				}

				// no class-scoped entry matched any context - the error stays deduplicated
				// in the trait and is matched as a whole below
			}

			$isStrippedSurvivor = isset($strippedSurvivors[spl_object_id($error)]);

			$filePath = $this->fileHelper->normalizePath($error->getFilePath());
			if (!$isStrippedSurvivor && isset($this->ignoreErrorsByFile[$filePath])) {
				foreach ($this->ignoreErrorsByFile[$filePath] as $ignoreError) {
					$i = $ignoreError['index'];
					$ignore = $ignoreError['ignoreError'];
					$result = $processIgnoreError($error, $i, $ignore);
					if (!$result) {
						$ignoredErrors[] = [$error, $ignore];
						continue 2;
					}
				}
			}

			$traitFilePath = $error->getTraitFilePath();
			if ($traitFilePath !== null) {
				$normalizedTraitFilePath = $this->fileHelper->normalizePath($traitFilePath);
				if (isset($this->ignoreErrorsByFile[$normalizedTraitFilePath])) {
					foreach ($this->ignoreErrorsByFile[$normalizedTraitFilePath] as $ignoreError) {
						$i = $ignoreError['index'];
						$ignore = $ignoreError['ignoreError'];
						$result = $processIgnoreError($error, $i, $ignore);
						if (!$result) {
							$ignoredErrors[] = [$error, $ignore];
							continue 2;
						}
					}
				}
			}

			foreach ($this->otherIgnoreErrors as $ignoreError) {
				$i = $ignoreError['index'];
				$ignore = $ignoreError['ignoreError'];

				if (
					$isStrippedSurvivor
					&& is_array($ignore)
					&& isset($ignore['path'])
					&& $error->getTraitFilePath() !== null
					&& !(new FileExcluder($this->fileHelper, [$ignore['path']]))->isExcludedFromAnalysing($error->getTraitFilePath())
				) {
					// the strip phase already counted this occurrence against
					// context-scoped path entries
					continue;
				}

				$result = $processIgnoreError($error, $i, $ignore);
				if (!$result) {
					$ignoredErrors[] = [$error, $ignore];
					continue 2;
				}
			}

			$notIgnoredErrors[] = $error;
		}

		$errors = $notIgnoredErrors;

		foreach ($unmatchedIgnoredErrors as $i => $unmatchedIgnoredError) {
			if (!is_array($unmatchedIgnoredError) || !isset($unmatchedIgnoredError['count']) || !isset($realCounts[$i])) {
				continue;
			}

			$realCount = $realCounts[$i];
			if ($realCount <= $unmatchedIgnoredError['count']) {
				continue;
			}

			$matchedFile = $matchedAt[$i]['file'] ?? null;
			$matchedLine = $matchedAt[$i]['line'] ?? null;

			$errors[] = (new Error(sprintf(
				'%s %s is expected to occur %d %s, but occurred %d %s.',
				IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError),
				IgnoredError::stringifyPattern($unmatchedIgnoredError),
				$unmatchedIgnoredError['count'],
				$unmatchedIgnoredError['count'] === 1 ? 'time' : 'times',
				$realCount,
				$realCount === 1 ? 'time' : 'times',
			), $matchedFile ?? '', $matchedLine, false))->withIdentifier('ignore.count');
		}

		$analysedFilesKeys = array_fill_keys($analysedFiles, true);

		if (!$hasInternalErrors) {
			foreach ($unmatchedIgnoredErrors as $i => $unmatchedIgnoredError) {
				$reportUnmatched = is_array($unmatchedIgnoredError)
					? ($unmatchedIgnoredError['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors)
					: $this->reportUnmatchedIgnoredErrors;
				if ($reportUnmatched === false) {
					continue;
				}
				$realCount = $realCounts[$i] ?? null;
				if (
					isset($unmatchedIgnoredError['count'])
					&& $realCount !== null
					&& (isset($unmatchedIgnoredError['realPath']) || !$onlyFiles)
				) {
					if ($realCount < $unmatchedIgnoredError['count']) {
						$matchedFile = $matchedAt[$i]['file'] ?? null;
						$matchedLine = $matchedAt[$i]['line'] ?? null;
						// $realCount is at least 1 (it was incremented in the closure)
						// and strictly less than count, so count is always >= 2.
						$errors[] = (new Error(sprintf(
							'%s %s is expected to occur %d times, but occurred only %d %s.',
							IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError),
							IgnoredError::stringifyPattern($unmatchedIgnoredError),
							$unmatchedIgnoredError['count'],
							$realCount,
							$realCount === 1 ? 'time' : 'times',
						), $matchedFile ?? '', $matchedLine, false))->withIdentifier('ignore.count');
					}
				} elseif (isset($unmatchedIgnoredError['realPath'])) {
					if (!array_key_exists($unmatchedIgnoredError['realPath'], $analysedFilesKeys)) {
						continue;
					}

					if ($onlyFiles) {
						continue;
					}

					$errors[] = (new Error(
						sprintf(
							'%s %s was not matched in reported errors.',
							IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError),
							IgnoredError::stringifyPattern($unmatchedIgnoredError),
						),
						$unmatchedIgnoredError['realPath'],
						canBeIgnored: false,
					))->withIdentifier('ignore.unmatched');
				} elseif (!$onlyFiles) {
					$stringErrors[] = sprintf(
						'%s %s was not matched in reported errors.',
						IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError),
						IgnoredError::stringifyPattern($unmatchedIgnoredError),
					);
				}
			}
		}

		return new IgnoredErrorHelperProcessedResult($errors, $ignoredErrors, $stringErrors);
	}

}
