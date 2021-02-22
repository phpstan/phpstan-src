<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileHelper;

class IgnoredErrorHelperResult
{

	private FileHelper $fileHelper;

	/** @var string[] */
	private array $errors;

	/** @var string[] */
	private array $warnings;

	/** @var array<array<mixed>> */
	private array $otherIgnoreErrors;

	/** @var array<string, array<array<mixed>>> */
	private array $ignoreErrorsByFile;

	/** @var (string|mixed[])[] */
	private array $ignoreErrors;

	private bool $reportUnmatchedIgnoredErrors;

	/**
	 * @param FileHelper $fileHelper
	 * @param string[] $errors
	 * @param string[] $warnings
	 * @param array<array<mixed>> $otherIgnoreErrors
	 * @param array<string, array<array<mixed>>> $ignoreErrorsByFile
	 * @param (string|mixed[])[] $ignoreErrors
	 * @param bool $reportUnmatchedIgnoredErrors
	 */
	public function __construct(
		FileHelper $fileHelper,
		array $errors,
		array $warnings,
		array $otherIgnoreErrors,
		array $ignoreErrorsByFile,
		array $ignoreErrors,
		bool $reportUnmatchedIgnoredErrors
	)
	{
		$this->fileHelper = $fileHelper;
		$this->errors = $errors;
		$this->warnings = $warnings;
		$this->otherIgnoreErrors = $otherIgnoreErrors;
		$this->ignoreErrorsByFile = $ignoreErrorsByFile;
		$this->ignoreErrors = $ignoreErrors;
		$this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
	}

	/**
	 * @return string[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return string[]
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}

	/**
	 * @param Error[] $errors
	 * @param string[] $analysedFiles
	 * @return string[]|Error[]
	 */
	public function process(
		array $errors,
		bool $onlyFiles,
		array $analysedFiles,
		bool $hasInternalErrors
	): array
	{
		$unmatchedIgnoredErrors = $this->ignoreErrors;
		$addErrors = [];

		$processIgnoreError = function (Error $error, int $i, $ignore) use (&$unmatchedIgnoredErrors, &$addErrors): bool {
			$shouldBeIgnored = false;
			if (is_string($ignore)) {
				$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore, null);
				if ($shouldBeIgnored) {
					unset($unmatchedIgnoredErrors[$i]);
				}
			} else {
				if (isset($ignore['path'])) {
					$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'], $ignore['path']);
					if ($shouldBeIgnored) {
						if (isset($ignore['count'])) {
							$realCount = $unmatchedIgnoredErrors[$i]['realCount'] ?? 0;
							$realCount++;
							$unmatchedIgnoredErrors[$i]['realCount'] = $realCount;

							if (!isset($unmatchedIgnoredErrors[$i]['file'])) {
								$unmatchedIgnoredErrors[$i]['file'] = $error->getFile();
								$unmatchedIgnoredErrors[$i]['line'] = $error->getLine();
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
						$shouldBeIgnored = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'], $ignorePath);
						if (!$shouldBeIgnored) {
							continue;
						}

						if (isset($unmatchedIgnoredErrors[$i])) {
							if (!is_array($unmatchedIgnoredErrors[$i])) {
								throw new \PHPStan\ShouldNotHappenException();
							}
							unset($unmatchedIgnoredErrors[$i]['paths'][$j]);
							if (isset($unmatchedIgnoredErrors[$i]['paths']) && count($unmatchedIgnoredErrors[$i]['paths']) === 0) {
								unset($unmatchedIgnoredErrors[$i]);
							}
						}
						break;
					}
				} else {
					throw new \PHPStan\ShouldNotHappenException();
				}
			}

			if ($shouldBeIgnored) {
				if (!$error->canBeIgnored()) {
					$addErrors[] = sprintf(
						'Error message "%s" cannot be ignored, use excludePaths instead.',
						$error->getMessage()
					);
					return true;
				}
				return false;
			}

			return true;
		};

		$errors = array_values(array_filter($errors, function (Error $error) use ($processIgnoreError): bool {
			$filePath = $this->fileHelper->normalizePath($error->getFilePath());
			if (isset($this->ignoreErrorsByFile[$filePath])) {
				foreach ($this->ignoreErrorsByFile[$filePath] as $ignoreError) {
					$i = $ignoreError['index'];
					$ignore = $ignoreError['ignoreError'];
					$result = $processIgnoreError($error, $i, $ignore);
					if (!$result) {
						return false;
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
							return false;
						}
					}
				}
			}

			foreach ($this->otherIgnoreErrors as $ignoreError) {
				$i = $ignoreError['index'];
				$ignore = $ignoreError['ignoreError'];

				$result = $processIgnoreError($error, $i, $ignore);
				if (!$result) {
					return false;
				}
			}

			return true;
		}));

		foreach ($unmatchedIgnoredErrors as $unmatchedIgnoredError) {
			if (!isset($unmatchedIgnoredError['count']) || !isset($unmatchedIgnoredError['realCount'])) {
				continue;
			}

			if ($unmatchedIgnoredError['realCount'] <= $unmatchedIgnoredError['count']) {
				continue;
			}

			$addErrors[] = new Error(sprintf(
				'Ignored error pattern %s is expected to occur %d %s, but occurred %d %s.',
				IgnoredError::stringifyPattern($unmatchedIgnoredError),
				$unmatchedIgnoredError['count'],
				$unmatchedIgnoredError['count'] === 1 ? 'time' : 'times',
				$unmatchedIgnoredError['realCount'],
				$unmatchedIgnoredError['realCount'] === 1 ? 'time' : 'times'
			), $unmatchedIgnoredError['file'], $unmatchedIgnoredError['line'], false);
		}

		$errors = array_merge($errors, $addErrors);

		$analysedFilesKeys = array_fill_keys($analysedFiles, true);

		if ($this->reportUnmatchedIgnoredErrors && !$hasInternalErrors) {
			foreach ($unmatchedIgnoredErrors as $unmatchedIgnoredError) {
				if (
					isset($unmatchedIgnoredError['count'])
					&& isset($unmatchedIgnoredError['realCount'])
					&& (isset($unmatchedIgnoredError['realPath']) || !$onlyFiles)
				) {
					if ($unmatchedIgnoredError['realCount'] < $unmatchedIgnoredError['count']) {
						$errors[] = new Error(sprintf(
							'Ignored error pattern %s is expected to occur %d %s, but occurred only %d %s.',
							IgnoredError::stringifyPattern($unmatchedIgnoredError),
							$unmatchedIgnoredError['count'],
							$unmatchedIgnoredError['count'] === 1 ? 'time' : 'times',
							$unmatchedIgnoredError['realCount'],
							$unmatchedIgnoredError['realCount'] === 1 ? 'time' : 'times'
						), $unmatchedIgnoredError['file'], $unmatchedIgnoredError['line'], false);
					}
				} elseif (isset($unmatchedIgnoredError['realPath'])) {
					if (!array_key_exists($unmatchedIgnoredError['realPath'], $analysedFilesKeys)) {
						continue;
					}

					$errors[] = new Error(
						sprintf(
							'Ignored error pattern %s was not matched in reported errors.',
							IgnoredError::stringifyPattern($unmatchedIgnoredError)
						),
						$unmatchedIgnoredError['realPath'],
						null,
						false
					);
				} elseif (!$onlyFiles) {
					$errors[] = sprintf(
						'Ignored error pattern %s was not matched in reported errors.',
						IgnoredError::stringifyPattern($unmatchedIgnoredError)
					);
				}
			}
		}

		return $errors;
	}

}
