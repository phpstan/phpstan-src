<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use function sprintf;

class RawErrorFormatter implements ErrorFormatter
{

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
	): int
	{
		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$output->writeRaw(sprintf('?:?:%s', $notFileSpecificError));
			$output->writeLineFormatted('');
		}

		$outputIdentifiers = $output->isVerbose();
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$identifier = '';
			if ($outputIdentifiers && $fileSpecificError->getIdentifier() !== null) {
				$identifier = " [identifier:{$fileSpecificError->getIdentifier()}]";
			}

			$output->writeRaw(
				sprintf(
					'%s:%d:%s%s',
					$fileSpecificError->getFile(),
					$fileSpecificError->getLine() ?? '?',
					$fileSpecificError->getMessage(),
					$identifier,
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
