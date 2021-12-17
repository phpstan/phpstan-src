<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use function sprintf;

class RawErrorFormatter implements ErrorFormatter
{

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int
	{
		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$output->writeRaw(sprintf('?:?:%s', $notFileSpecificError));
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$output->writeRaw(
				sprintf(
					'%s:%d:%s',
					$fileSpecificError->getFile(),
					$fileSpecificError->getLine() ?? '?',
					$fileSpecificError->getMessage(),
				),
			);
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getWarnings() as $warning) {
			$output->writeRaw(sprintf('?:?:%s', $warning));
			$output->writeLineFormatted('');
		}

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
