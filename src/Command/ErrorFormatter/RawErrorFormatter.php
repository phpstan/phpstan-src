<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

class RawErrorFormatter implements ErrorFormatter
{

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int
	{
		if (!$analysisResult->hasErrors()) {
			return 0;
		}

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
					$fileSpecificError->getMessage()
				)
			);
			$output->writeLineFormatted('');
		}

		return 1;
	}

}
