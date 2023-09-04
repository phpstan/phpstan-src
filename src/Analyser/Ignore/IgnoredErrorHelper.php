<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\FileHelper;
use function array_key_exists;
use function array_values;
use function is_array;
use function is_file;
use function sprintf;

class IgnoredErrorHelper
{

	/**
	 * @param (string|mixed[])[] $ignoreErrors
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private array $ignoreErrors,
		private bool $reportUnmatchedIgnoredErrors,
	)
	{
	}

	public function initialize(): IgnoredErrorHelperResult
	{
		$otherIgnoreErrors = [];
		$ignoreErrorsByFile = [];
		$errors = [];

		$expandedIgnoreErrors = [];
		foreach ($this->ignoreErrors as $ignoreError) {
			if (is_array($ignoreError)) {
				if (!isset($ignoreError['message']) && !isset($ignoreError['messages'])) {
					$errors[] = sprintf(
						'Ignored error %s is missing a message.',
						Json::encode($ignoreError),
					);
					continue;
				}
				if (isset($ignoreError['messages'])) {
					foreach ($ignoreError['messages'] as $message) {
						$expandedIgnoreError = $ignoreError;
						unset($expandedIgnoreError['messages']);
						$expandedIgnoreError['message'] = $message;
						$expandedIgnoreErrors[] = $expandedIgnoreError;
					}
				} else {
					$expandedIgnoreErrors[] = $ignoreError;
				}
			} else {
				$expandedIgnoreErrors[] = $ignoreError;
			}
		}

		$uniquedExpandedIgnoreErrors = [];
		foreach ($expandedIgnoreErrors as $ignoreError) {
			if (!isset($ignoreError['message'])) {
				$uniquedExpandedIgnoreErrors[] = $ignoreError;
				continue;
			}
			if (!isset($ignoreError['path'])) {
				$uniquedExpandedIgnoreErrors[] = $ignoreError;
				continue;
			}

			$key = sprintf("%s\n%s", $ignoreError['message'], $ignoreError['path']);
			if (isset($ignoreError['identifier'])) {
				$key = sprintf("%s\n%s", $key, $ignoreError['identifier']);
			}
			if (!array_key_exists($key, $uniquedExpandedIgnoreErrors)) {
				$uniquedExpandedIgnoreErrors[$key] = $ignoreError;
				continue;
			}

			$uniquedExpandedIgnoreErrors[$key] = [
				'message' => $ignoreError['message'],
				'path' => $ignoreError['path'],
				'identifier' => $ignoreError['identifier'] ?? null,
				'count' => ($uniquedExpandedIgnoreErrors[$key]['count'] ?? 1) + ($ignoreError['count'] ?? 1),
				'reportUnmatched' => ($uniquedExpandedIgnoreErrors[$key]['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors) || ($ignoreError['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors),
			];
		}

		$expandedIgnoreErrors = array_values($uniquedExpandedIgnoreErrors);

		foreach ($expandedIgnoreErrors as $i => $ignoreError) {
			$ignoreErrorEntry = [
				'index' => $i,
				'ignoreError' => $ignoreError,
			];
			try {
				if (is_array($ignoreError)) {
					if (!isset($ignoreError['message'])) {
						$errors[] = sprintf(
							'Ignored error %s is missing a message.',
							Json::encode($ignoreError),
						);
						continue;
					}
					if (!isset($ignoreError['path'])) {
						$otherIgnoreErrors[] = $ignoreErrorEntry;
					} elseif (@is_file($ignoreError['path'])) {
						$normalizedPath = $this->fileHelper->normalizePath($ignoreError['path']);
						$ignoreError['path'] = $normalizedPath;
						$ignoreErrorsByFile[$normalizedPath][] = $ignoreErrorEntry;
						$ignoreError['realPath'] = $normalizedPath;
						$expandedIgnoreErrors[$i] = $ignoreError;
					} else {
						$otherIgnoreErrors[] = $ignoreErrorEntry;
					}
				} else {
					$otherIgnoreErrors[] = $ignoreErrorEntry;
				}
			} catch (JsonException $e) {
				$errors[] = $e->getMessage();
			}
		}

		return new IgnoredErrorHelperResult($this->fileHelper, $errors, $otherIgnoreErrors, $ignoreErrorsByFile, $expandedIgnoreErrors, $this->reportUnmatchedIgnoredErrors);
	}

}
