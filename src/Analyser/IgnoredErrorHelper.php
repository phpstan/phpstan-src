<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\FileHelper;
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
						if (!isset($ignoreError['paths']) && !isset($ignoreError['reportUnmatched'])) {
							$errors[] = sprintf(
								'Ignored error %s is missing a path, paths or reportUnmatched.',
								Json::encode($ignoreError),
							);
						}

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
