<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Command\Output;
use PHPStan\Diagnose\DiagnoseExtension;
use PHPStan\File\RelativePathHelper;
use function count;
use function sprintf;

final class ProcessedFilesDiagnoseExtension implements DiagnoseExtension
{

	public function __construct(
		private ProcessedFilesCollector $processedFilesCollector,
		private RelativePathHelper $simpleRelativePathHelper,
	)
	{
	}

	public function print(Output $output): void
	{
		$topFiles = $this->processedFilesCollector->getTopMostAnalysedFiles(5);
		if (count($topFiles) === 0) {
			return;
		}

		$output->writeLineFormatted('<info>Most often analysed files:</info>');
		foreach ($topFiles as $file => $count) {
			$output->writeLineFormatted(sprintf(
				'  %s: %d times',
				$this->simpleRelativePathHelper->getRelativePath($file),
				$count,
			));
		}
		$output->writeLineFormatted('');
	}

}
