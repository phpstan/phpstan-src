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

			$line = sprintf('::error %s::%s', implode(',', $metas), $message);

			$output->writeRaw($line);
			$output->writeLineFormatted('');
		}

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
