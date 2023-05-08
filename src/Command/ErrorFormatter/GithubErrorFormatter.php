<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use InvalidArgumentException;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function array_walk;
use function implode;
use function in_array;
use function sprintf;
use function str_replace;

/**
 * Allow errors to be reported in pull-requests diff when run in a GitHub Action
 * @see https://help.github.com/en/actions/reference/workflow-commands-for-github-actions#setting-an-error-message
 */
class GithubErrorFormatter implements ErrorFormatter
{

	public function __construct(
		private RelativePathHelper $relativePathHelper,
		private string $errorLevel = 'error',
	)
	{
		if (!in_array($errorLevel, ['notice', 'warning', 'error'], true)) {
			throw new InvalidArgumentException(sprintf('Invalid error level "%s"', $errorLevel));
		}
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
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

			$line = sprintf('::%s %s::%s', $this->errorLevel, implode(',', $metas), $message);

			$output->writeRaw($line);
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			// newlines need to be encoded
			// see https://github.com/actions/starter-workflows/issues/68#issuecomment-581479448
			$notFileSpecificError = str_replace("\n", '%0A', $notFileSpecificError);

			$line = sprintf('::%s ::%s', $this->errorLevel, $notFileSpecificError);

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
