<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

/**
 * Allow errors to be reported in pull-requests diff when run in a GitHub Action
 * @see https://help.github.com/en/actions/reference/workflow-commands-for-github-actions#setting-an-error-message
 */
class GithubErrorFormatter implements ErrorFormatter
{

	private RelativePathHelper $relativePathHelper;

	private TableErrorFormatter $tableErrorformatter;

	public function __construct(
		RelativePathHelper $relativePathHelper,
		TableErrorFormatter $tableErrorformatter
	)
	{
		$this->relativePathHelper = $relativePathHelper;
		$this->tableErrorformatter = $tableErrorformatter;
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$this->tableErrorformatter->formatErrors($analysisResult, $output);

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$metas = [
				'file' => $this->relativePathHelper->getRelativePath($fileSpecificError->getFile()),
				'line' => $fileSpecificError->getLine(),
				'col' => 0,
			];
			array_walk($metas, static function (&$value, string $key): void {
				$value = sprintf('%s=%s', $key, (string) $value);
			});

			$message = $fileSpecificError->getMessage();
			// newlines need to be encoded
			// see https://github.com/actions/starter-workflows/issues/68#issuecomment-581479448
			$message = str_replace("\n", '%0A', $message);

			$line = sprintf('::error %s::%s', implode(',', $metas), $message);

			$output->writeRaw($line);
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			// newlines need to be encoded
			// see https://github.com/actions/starter-workflows/issues/68#issuecomment-581479448
			$notFileSpecificError = str_replace("\n", '%0A', $notFileSpecificError);

			$line = sprintf('::error ::%s', $notFileSpecificError);

			$output->writeRaw($line);
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getWarnings() as $warning) {
			// newlines need to be encoded
			// see https://github.com/actions/starter-workflows/issues/68#issuecomment-581479448
			$warning = str_replace("\n", '%0A', $warning);

			$line = sprintf('::warning ::%s', $warning);

			$output->writeRaw($line);
			$output->writeLineFormatted('');
		}

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
