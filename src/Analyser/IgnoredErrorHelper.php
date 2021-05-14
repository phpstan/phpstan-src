<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Nette\Utils\Json;
use PHPStan\Command\IgnoredRegexValidator;
use PHPStan\File\FileHelper;

class IgnoredErrorHelper
{

	private IgnoredRegexValidator $ignoredRegexValidator;

	private \PHPStan\File\FileHelper $fileHelper;

	/** @var (string|mixed[])[] */
	private array $ignoreErrors;

	private bool $reportUnmatchedIgnoredErrors;

	/**
	 * @param IgnoredRegexValidator $ignoredRegexValidator
	 * @param FileHelper $fileHelper
	 * @param (string|mixed[])[] $ignoreErrors
	 * @param bool $reportUnmatchedIgnoredErrors
	 */
	public function __construct(
		IgnoredRegexValidator $ignoredRegexValidator,
		FileHelper $fileHelper,
		array $ignoreErrors,
		bool $reportUnmatchedIgnoredErrors
	)
	{
		$this->ignoredRegexValidator = $ignoredRegexValidator;
		$this->fileHelper = $fileHelper;
		$this->ignoreErrors = $ignoreErrors;
		$this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
	}

	public function initialize(): IgnoredErrorHelperResult
	{
		$otherIgnoreErrors = [];
		$ignoreErrorsByFile = [];
		$warnings = [];
		$errors = [];
		foreach ($this->ignoreErrors as $i => $ignoreError) {
			try {
				if (is_array($ignoreError)) {
					if (!isset($ignoreError['message'])) {
						$errors[] = sprintf(
							'Ignored error %s is missing a message.',
							Json::encode($ignoreError)
						);
						continue;
					}
					if (!isset($ignoreError['path'])) {
						if (!isset($ignoreError['paths'])) {
							$errors[] = sprintf(
								'Ignored error %s is missing a path.',
								Json::encode($ignoreError)
							);
						}

						$otherIgnoreErrors[] = [
							'index' => $i,
							'ignoreError' => $ignoreError,
						];
					} elseif (@is_file($ignoreError['path'])) {
						$normalizedPath = $this->fileHelper->normalizePath($ignoreError['path']);
						$ignoreError['path'] = $normalizedPath;
						$ignoreErrorsByFile[$normalizedPath][] = [
							'index' => $i,
							'ignoreError' => $ignoreError,
						];
						$ignoreError['realPath'] = $normalizedPath;
						$this->ignoreErrors[$i] = $ignoreError;
					} else {
						$otherIgnoreErrors[] = [
							'index' => $i,
							'ignoreError' => $ignoreError,
						];
					}

					$ignoreMessage = $ignoreError['message'];
					\Nette\Utils\Strings::match('', $ignoreMessage);
					if (isset($ignoreError['count'])) {
						continue; // ignoreError coming from baseline will be correct
					}
					$validationResult = $this->ignoredRegexValidator->validate($ignoreMessage);
					$ignoredTypes = $validationResult->getIgnoredTypes();
					if (count($ignoredTypes) > 0) {
						$warnings[] = $this->createIgnoredTypesWarning($ignoreMessage, $ignoredTypes);
					}

					if ($validationResult->hasAnchorsInTheMiddle()) {
						$warnings[] = $this->createAnchorInTheMiddleWarning($ignoreMessage);
					}

					if ($validationResult->areAllErrorsIgnored()) {
						$errors[] = sprintf("Ignored error %s has an unescaped '%s' which leads to ignoring all errors. Use '%s' instead.", $ignoreMessage, $validationResult->getWrongSequence(), $validationResult->getEscapedWrongSequence());
					}
				} else {
					$otherIgnoreErrors[] = [
						'index' => $i,
						'ignoreError' => $ignoreError,
					];
					$ignoreMessage = $ignoreError;
					\Nette\Utils\Strings::match('', $ignoreMessage);
					$validationResult = $this->ignoredRegexValidator->validate($ignoreMessage);
					$ignoredTypes = $validationResult->getIgnoredTypes();
					if (count($ignoredTypes) > 0) {
						$warnings[] = $this->createIgnoredTypesWarning($ignoreMessage, $ignoredTypes);
					}

					if ($validationResult->hasAnchorsInTheMiddle()) {
						$warnings[] = $this->createAnchorInTheMiddleWarning($ignoreMessage);
					}

					if ($validationResult->areAllErrorsIgnored()) {
						$errors[] = sprintf("Ignored error %s has an unescaped '%s' which leads to ignoring all errors. Use '%s' instead.", $ignoreMessage, $validationResult->getWrongSequence(), $validationResult->getEscapedWrongSequence());
					}
				}
			} catch (\Nette\Utils\RegexpException $e) {
				$errors[] = $e->getMessage();
			} catch (\Nette\Utils\JsonException $e) {
				$errors[] = $e->getMessage();
			}
		}

		return new IgnoredErrorHelperResult($this->fileHelper, $errors, $warnings, $otherIgnoreErrors, $ignoreErrorsByFile, $this->ignoreErrors, $this->reportUnmatchedIgnoredErrors);
	}

	/**
	 * @param string $regex
	 * @param array<string, string> $ignoredTypes
	 * @return string
	 */
	private function createIgnoredTypesWarning(string $regex, array $ignoredTypes): string
	{
		return sprintf(
			"Ignored error %s has an unescaped '|' which leads to ignoring more errors than intended. Use '\\|' instead.\n%s",
			$regex,
			sprintf(
				"It ignores all errors containing the following types:\n%s",
				implode("\n", array_map(static function (string $typeDescription): string {
					return sprintf('* %s', $typeDescription);
				}, array_keys($ignoredTypes)))
			)
		);
	}

	private function createAnchorInTheMiddleWarning(string $regex): string
	{
		return sprintf("Ignored error %s has an unescaped anchor '$' in the middle. This leads to unintended behavior. Use '\\$' instead.", $regex);
	}

}
